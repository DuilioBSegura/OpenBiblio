<?php
/**
 * OpenBiblio - Exclusão / Inativação Segura de Livros (RN-05)
 */
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: livros.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('danger', 'Validação de segurança falhou (CSRF token inválido).');
    header('Location: livros.php');
    exit;
}

$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('danger', 'ID de livro inválido.');
    header('Location: livros.php');
    exit;
}

$db = getDBConnection();

try {
    // 1. Verificar se o livro existe
    $stmtLivro = $db->prepare("SELECT id, titulo FROM livros WHERE id = ? LIMIT 1");
    $stmtLivro->execute([$id]);
    $livro = $stmtLivro->fetch();

    if (!$livro) {
        setFlashMessage('danger', 'Livro não encontrado.');
        header('Location: livros.php');
        exit;
    }

    // 2. Verificar se possui histórico de empréstimos (RN-05)
    $stmtCheckLoans = $db->prepare("SELECT COUNT(*) FROM emprestimos WHERE livro_id = ?");
    $stmtCheckLoans->execute([$id]);
    $totalEmprestimos = (int)$stmtCheckLoans->fetchColumn();

    if ($totalEmprestimos > 0) {
        // Possui histórico: inativar o livro e seus exemplares ao invés de apagar (soft delete)
        $db->beginTransaction();

        $stmtInativarLivro = $db->prepare("UPDATE livros SET ativo = 0, status = 'Indisponível' WHERE id = ?");
        $stmtInativarLivro->execute([$id]);

        $stmtInativarEx = $db->prepare("UPDATE exemplares SET status = 'Inativo' WHERE livro_id = ?");
        $stmtInativarEx->execute([$id]);

        $db->commit();
        auditLog('LIVRO_INATIVADO', "Livro ID {$id} ('{$livro['titulo']}') inativado por conter histórico de empréstimos.");

        setFlashMessage('warning', "Este livro possui histórico de empréstimos e não pode ser excluído permanentemente. Ele foi inativado com sucesso.");
        header('Location: livros.php');
        exit;
    } else {
        // Não possui histórico: pode excluir os exemplares e o livro
        $db->beginTransaction();

        $stmtDelEx = $db->prepare("DELETE FROM exemplares WHERE livro_id = ?");
        $stmtDelEx->execute([$id]);

        $stmtDelLivro = $db->prepare("DELETE FROM livros WHERE id = ?");
        $stmtDelLivro->execute([$id]);

        $db->commit();
        auditLog('LIVRO_EXCLUIDO', "Livro ID {$id} ('{$livro['titulo']}') excluído com sucesso.");

        setFlashMessage('success', "Livro '{$livro['titulo']}' removido do acervo com sucesso!");
        header('Location: livros.php');
        exit;
    }
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Erro ao excluir livro: " . $e->getMessage());
    setFlashMessage('danger', "Erro ao processar exclusão: " . $e->getMessage());
    header('Location: livros.php');
    exit;
}
