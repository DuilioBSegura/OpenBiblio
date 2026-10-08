<?php
/**
 * OpenBiblio - Processamento Transacional de Empréstimo (RN-01, RN-02, RN-03, RN-04, RN-13)
 */
require_once __DIR__ . '/includes/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: emprestimos.php');
    exit;
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('danger', 'Erro de segurança CSRF.');
    header('Location: emprestimos.php');
    exit;
}

$leitor_id               = filter_input(INPUT_POST, 'leitor_id', FILTER_VALIDATE_INT);
$livro_id                = filter_input(INPUT_POST, 'livro_id', FILTER_VALIDATE_INT);
$exemplar_id             = filter_input(INPUT_POST, 'exemplar_id', FILTER_VALIDATE_INT);
$data_emprestimo         = trim($_POST['data_emprestimo'] ?? '');
$data_prevista_devolucao = trim($_POST['data_prevista_devolucao'] ?? '');
$observacoes             = trim($_POST['observacoes'] ?? '');
$usuario_id              = $_SESSION['usuario_id'];

if (!$leitor_id || !$livro_id || !$exemplar_id || empty($data_emprestimo) || empty($data_prevista_devolucao)) {
    setFlashMessage('danger', 'Preencha todos os campos obrigatórios para o empréstimo.');
    header('Location: emprestimo_novo.php');
    exit;
}

$db = getDBConnection();

try {
    // Iniciar Transação Atômica
    $db->beginTransaction();

    // 1. Verificar Leitor e Contar Empréstimos Ativos (RN-04: Máximo 3 empréstimos simultâneos)
    $stmtLeitor = $db->prepare("SELECT id, nome_completo, ativo FROM leitores WHERE id = ? FOR UPDATE");
    $stmtLeitor->execute([$leitor_id]);
    $leitor = $stmtLeitor->fetch();

    if (!$leitor || (int)$leitor['ativo'] !== 1) {
        throw new Exception("Leitor selecionado é inválido ou está inativo.");
    }

    $stmtCount = $db->prepare("SELECT COUNT(*) FROM emprestimos WHERE leitor_id = ? AND status = 'Ativo'");
    $stmtCount->execute([$leitor_id]);
    $emprestimosAtivos = (int)$stmtCount->fetchColumn();

    if ($emprestimosAtivos >= 3) {
        throw new Exception("O leitor {$leitor['nome_completo']} já possui o limite máximo de 3 empréstimos ativos simultaneamente (RN-04).");
    }

    // 2. Verificar Exemplar e Status (RN-01 & RN-02: Exemplar deve estar disponível e não já emprestado)
    $stmtExemplar = $db->prepare("SELECT id, codigo_exemplar, status FROM exemplares WHERE id = ? AND livro_id = ? FOR UPDATE");
    $stmtExemplar->execute([$exemplar_id, $livro_id]);
    $exemplar = $stmtExemplar->fetch();

    if (!$exemplar) {
        throw new Exception("Exemplar não encontrado para este livro.");
    }

    if ($exemplar['status'] !== 'Disponível') {
        throw new Exception("O exemplar código {$exemplar['codigo_exemplar']} não está disponível para empréstimo (Status atual: {$exemplar['status']}).");
    }

    // 3. Verificar se o Livro possui quantidade_disponivel > 0
    $stmtLivro = $db->prepare("SELECT id, titulo, quantidade_disponivel FROM livros WHERE id = ? FOR UPDATE");
    $stmtLivro->execute([$livro_id]);
    $livro = $stmtLivro->fetch();

    if (!$livro || $livro['quantidade_disponivel'] <= 0) {
        throw new Exception("O livro '{$livro['titulo']}' não possui exemplares disponíveis em estoque.");
    }

    // 4. Inserir Registro de Empréstimo
    $stmtInsertEmp = $db->prepare("
        INSERT INTO emprestimos (leitor_id, livro_id, exemplar_id, usuario_id, data_emprestimo, data_prevista_devolucao, status, observacoes)
        VALUES (?, ?, ?, ?, ?, ?, 'Ativo', ?)
    ");
    $stmtInsertEmp->execute([
        $leitor_id,
        $livro_id,
        $exemplar_id,
        $usuario_id,
        $data_emprestimo,
        $data_prevista_devolucao,
        $observacoes ?: null
    ]);
    $emprestimoId = $db->lastInsertId();

    // 5. Atualizar Status do Exemplar para 'Emprestado'
    $stmtUpdateEx = $db->prepare("UPDATE exemplares SET status = 'Emprestado' WHERE id = ?");
    $stmtUpdateEx->execute([$exemplar_id]);

    // 6. Atualizar quantidade_disponivel do Livro
    $stmtUpdateLivro = $db->prepare("UPDATE livros SET quantidade_disponivel = GREATEST(0, quantidade_disponivel - 1) WHERE id = ?");
    $stmtUpdateLivro->execute([$livro_id]);

    // Commit da Transação
    $db->commit();
    auditLog('EMPRESTIMO_CRIADO', "Empréstimo ID {$emprestimoId} criado: Livro '{$livro['titulo']}', Exemplar {$exemplar['codigo_exemplar']}, Leitor '{$leitor['nome_completo']}'.");

    setFlashMessage('success', "Empréstimo #{$emprestimoId} registrado com sucesso para {$leitor['nome_completo']}!");
    header("Location: recibo.php?id={$emprestimoId}");
    exit;

} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Erro na transação de empréstimo: " . $e->getMessage());
    $userMsg = ($e instanceof PDOException) ? 'Erro interno no banco de dados ao salvar empréstimo.' : $e->getMessage();
    setFlashMessage('danger', "Falha ao registrar empréstimo: " . $userMsg);
    header('Location: emprestimo_novo.php');
    exit;
}
