<?php
/**
 * OpenBiblio - Processamento Transacional de Devolução (RN-07, RN-08, RN-09)
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

$emprestimo_id   = filter_input(INPUT_POST, 'emprestimo_id', FILTER_VALIDATE_INT);
$estado_exemplar = trim($_POST['estado_exemplar'] ?? 'Perfeito estado');
$observacoes     = trim($_POST['observacoes'] ?? '');
$usuario_id      = $_SESSION['usuario_id'];

if (!$emprestimo_id) {
    setFlashMessage('danger', 'ID de empréstimo inválido.');
    header('Location: emprestimos.php');
    exit;
}

$db = getDBConnection();

try {
    // 1. Iniciar Transação Atômica (RN-08)
    $db->beginTransaction();

    // 2. Buscar Empréstimo com FOR UPDATE (RN-07: Apenas empréstimo Ativo)
    $stmtEmp = $db->prepare("
        SELECT e.id, e.leitor_id, e.livro_id, e.exemplar_id, e.status, 
               l.nome_completo AS leitor_nome, b.titulo AS livro_titulo
        FROM emprestimos e
        JOIN leitores l ON e.leitor_id = l.id
        JOIN livros b ON e.livro_id = b.id
        WHERE e.id = ? 
        FOR UPDATE
    ");
    $stmtEmp->execute([$emprestimo_id]);
    $emp = $stmtEmp->fetch();

    if (!$emp) {
        throw new Exception("Registro de empréstimo não encontrado.");
    }

    if ($emp['status'] !== 'Ativo') {
        throw new Exception("Uma devolução só pode ser registrada para um empréstimo ativo (RN-07). Status atual: {$emp['status']}.");
    }

    // 3. Inserir Registro em devolucoes (Histórico de Auditoria Imutável)
    $stmtInsertDev = $db->prepare("
        INSERT INTO devolucoes (emprestimo_id, usuario_id, data_devolucao_real, estado_exemplar, observacoes)
        VALUES (?, ?, NOW(), ?, ?)
    ");
    $stmtInsertDev->execute([
        $emprestimo_id,
        $usuario_id,
        $estado_exemplar,
        $observacoes ?: null
    ]);

    // 4. Alterar Status do Empréstimo para 'Concluído'
    $stmtUpdateEmp = $db->prepare("UPDATE emprestimos SET status = 'Concluído' WHERE id = ?");
    $stmtUpdateEmp->execute([$emprestimo_id]);

    // 5. Alterar Status do Exemplar (Disponível ou Manutenção se avariado)
    $novoStatusEx = ($estado_exemplar === 'Danificado' || $estado_exemplar === 'Em manutenção') ? 'Manutenção' : 'Disponível';
    $stmtUpdateEx = $db->prepare("UPDATE exemplares SET status = ? WHERE id = ?");
    $stmtUpdateEx->execute([$novoStatusEx, $emp['exemplar_id']]);

    // 6. Incrementar quantidade_disponivel do Livro (se exemplar voltou a ficar disponível)
    if ($novoStatusEx === 'Disponível') {
        $stmtUpdateLivro = $db->prepare("UPDATE livros SET quantidade_disponivel = quantidade_disponivel + 1 WHERE id = ?");
        $stmtUpdateLivro->execute([$emp['livro_id']]);
    }

    // 7. COMMIT da Transação
    $db->commit();
    auditLog('DEVOLUCAO_REGISTRADA', "Devolução efetuada para Empréstimo ID {$emprestimo_id}: Livro '{$emp['livro_titulo']}', Leitor '{$emp['leitor_nome']}', Estado: {$estado_exemplar}.");

    setFlashMessage('success', "Devolução registrada com sucesso! Estoque sincronizado.");
    header('Location: devolucoes.php');
    exit;

} catch (Exception $e) {
    // 8. ROLLBACK em caso de falha (RN-09)
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Erro na transação de devolução: " . $e->getMessage());
    $userMsg = ($e instanceof PDOException) ? 'Erro interno no banco de dados.' : $e->getMessage();
    setFlashMessage('danger', "Falha ao registrar devolução: " . $userMsg);
    header('Location: emprestimos.php');
    exit;
}
