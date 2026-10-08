<?php
/**
 * OpenBiblio - Renovação / Edição de Empréstimo
 */
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: emprestimos.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('danger', 'Erro CSRF Token.');
    header('Location: emprestimos.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
$novaDataDevolucao = trim($_POST['data_prevista_devolucao'] ?? '');
$observacoes = trim($_POST['observacoes'] ?? '');

if (!$id || empty($novaDataDevolucao)) {
    setFlashMessage('danger', 'Dados inválidos para renovação de empréstimo.');
    header('Location: emprestimos.php');
    exit;
}

$db = getDBConnection();

try {
    $stmtEmp = $db->prepare("SELECT id, status FROM emprestimos WHERE id = ? LIMIT 1");
    $stmtEmp->execute([$id]);
    $emp = $stmtEmp->fetch();

    if (!$emp || $emp['status'] !== 'Ativo') {
        setFlashMessage('danger', 'Apenas empréstimos ativos podem ser renovados.');
        header('Location: emprestimos.php');
        exit;
    }

    $stmtUpdate = $db->prepare("
        UPDATE emprestimos 
        SET data_prevista_devolucao = ?, observacoes = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([$novaDataDevolucao, $observacoes ?: null, $id]);

    auditLog('EMPRESTIMO_RENOVADO', "Empréstimo ID {$id} renovado até {$novaDataDevolucao}.");
    setFlashMessage('success', "Empréstimo #{$id} renovado com sucesso até " . date('d/m/Y', strtotime($novaDataDevolucao)) . "!");
    header('Location: emprestimos.php');
    exit;
} catch (Exception $e) {
    error_log("Erro na renovação de empréstimo: " . $e->getMessage());
    setFlashMessage('danger', "Erro no servidor ao renovar empréstimo.");
    header('Location: emprestimos.php');
    exit;
}
