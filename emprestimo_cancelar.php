<?php
/**
 * OpenBiblio - Cancelamento Transacional de Empréstimo
 */
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: emprestimos.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('danger', 'Erro CSRF.');
    header('Location: emprestimos.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('danger', 'ID de empréstimo inválido.');
    header('Location: emprestimos.php');
    exit;
}

$db = getDBConnection();

try {
    $db->beginTransaction();

    // 1. Buscar empréstimo com bloqueio
    $stmtEmp = $db->prepare("SELECT id, livro_id, exemplar_id, status FROM emprestimos WHERE id = ? FOR UPDATE");
    $stmtEmp->execute([$id]);
    $emp = $stmtEmp->fetch();

    if (!$emp) {
        throw new Exception("Empréstimo não encontrado.");
    }

    if ($emp['status'] !== 'Ativo') {
        throw new Exception("Apenas empréstimos ativos podem ser cancelados.");
    }

    // 2. Alterar status para Cancelado
    $stmtUpdateEmp = $db->prepare("UPDATE emprestimos SET status = 'Cancelado', data_cancelamento = NOW() WHERE id = ?");
    $stmtUpdateEmp->execute([$id]);

    // 3. Devolver disponibilidade do exemplar
    $stmtUpdateEx = $db->prepare("UPDATE exemplares SET status = 'Disponível' WHERE id = ?");
    $stmtUpdateEx->execute([$emp['exemplar_id']]);

    // 4. Incrementar quantidade_disponivel do livro
    $stmtUpdateLivro = $db->prepare("UPDATE livros SET quantidade_disponivel = quantidade_disponivel + 1 WHERE id = ?");
    $stmtUpdateLivro->execute([$emp['livro_id']]);

    $db->commit();
    auditLog('EMPRESTIMO_CANCELADO', "Empréstimo ID {$id} foi cancelado administrativamente.");

    setFlashMessage('info', "Empréstimo #{$id} foi cancelado e o exemplar voltou a ficar disponível no acervo.");
    header('Location: emprestimos.php');
    exit;
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Erro ao cancelar empréstimo: " . $e->getMessage());
    setFlashMessage('danger', "Erro no servidor ao cancelar empréstimo: " . $e->getMessage());
    header('Location: emprestimos.php');
    exit;
}
