<?php
/**
 * OpenBiblio - Exclusão / Inativação de Leitor
 */
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: leitores.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('danger', 'Erro CSRF.');
    header('Location: leitores.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('danger', 'ID de leitor inválido.');
    header('Location: leitores.php');
    exit;
}

$db = getDBConnection();

try {
    $stmtLeitor = $db->prepare("SELECT id, nome_completo FROM leitores WHERE id = ? LIMIT 1");
    $stmtLeitor->execute([$id]);
    $leitor = $stmtLeitor->fetch();

    if (!$leitor) {
        setFlashMessage('danger', 'Leitor não encontrado.');
        header('Location: leitores.php');
        exit;
    }

    // Verificar histórico de empréstimos
    $stmtCheckLoans = $db->prepare("SELECT COUNT(*) FROM emprestimos WHERE leitor_id = ?");
    $stmtCheckLoans->execute([$id]);
    $totalLoans = (int)$stmtCheckLoans->fetchColumn();

    if ($totalLoans > 0) {
        // Possui histórico: inativar leitor (soft delete)
        $stmtInativar = $db->prepare("UPDATE leitores SET ativo = 0 WHERE id = ?");
        $stmtInativar->execute([$id]);

        auditLog('LEITOR_INATIVADO', "Leitor ID {$id} ('{$leitor['nome_completo']}') inativado.");
        setFlashMessage('warning', "O leitor '{$leitor['nome_completo']}' possui histórico de empréstimos e foi inativado ao invés de excluído.");
    } else {
        // Sem histórico: pode deletar
        $stmtDelete = $db->prepare("DELETE FROM leitores WHERE id = ?");
        $stmtDelete->execute([$id]);

        auditLog('LEITOR_EXCLUIDO', "Leitor ID {$id} ('{$leitor['nome_completo']}') excluído.");
        setFlashMessage('success', "Leitor '{$leitor['nome_completo']}' excluído com sucesso!");
    }

    header('Location: leitores.php');
    exit;
} catch (Exception $e) {
    error_log("Erro ao excluir leitor: " . $e->getMessage());
    setFlashMessage('danger', "Erro no servidor ao processar exclusão.");
    header('Location: leitores.php');
    exit;
}
