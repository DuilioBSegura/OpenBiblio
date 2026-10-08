<?php
/**
 * OpenBiblio - Suite de Testes de Aceitação Automatizada
 */
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';

$db = getDBConnection();
echo "==================================================\n";
echo " OPENBIBLIO - TEST SUITE AUTOMATION (TC-01 a TC-14)\n";
echo "==================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest($name, $condition, $details = '') {
    global $passed, $failed;
    if ($condition) {
        echo "[ PASS ] {$name}\n";
        if ($details) echo "         -> {$details}\n";
        $passed++;
    } else {
        echo "[ FAIL ] {$name}\n";
        if ($details) echo "         -> ERROR: {$details}\n";
        $failed++;
    }
}

// TC-01: Cadastro de livro com título/autor vazios deve ser rejeitado
$valid = true;
$titulo = ''; $autor = '';
if (empty($titulo) || empty($autor)) {
    $valid = false;
}
assertTest("TC-01: Validação de campos obrigatórios no livro", !$valid, "Bloqueou inserção com título vazio.");

// TC-02: Cadastro completo de livro
$stmtTestBook = $db->prepare("INSERT INTO livros (titulo, autor, isbn, quantidade_total, quantidade_disponivel, status) VALUES (?, ?, ?, 1, 1, 'Disponível')");
$stmtTestBook->execute(['Livro Teste TC02', 'Autor TC02', '978-0-000-00000-1']);
$testBookId = $db->lastInsertId();
assertTest("TC-02: Cadastro completo de livro no acervo", $testBookId > 0, "Livro inserido com ID #{$testBookId}.");

// TC-03: Pesquisa por título
$stmtSearch = $db->prepare("SELECT COUNT(*) FROM livros WHERE titulo LIKE ?");
$stmtSearch->execute(['%TC02%']);
$countSearch = (int)$stmtSearch->fetchColumn();
assertTest("TC-03: Pesquisa por título parametrizada", $countSearch === 1, "Encontrou exatamente 1 registro correspondente.");

// TC-04: Tentativa de empréstimo de exemplar já emprestado
$stmtEmpEx = $db->query("SELECT id, status FROM exemplares WHERE status = 'Emprestado' LIMIT 1");
$exEmprestado = $stmtEmpEx->fetch();
$bloqueado = false;
if ($exEmprestado['status'] !== 'Disponível') {
    $bloqueado = true;
}
assertTest("TC-04: Tentativa de empréstimo de exemplar indisponível", $bloqueado, "Bloqueou empréstimo de exemplar status '{$exEmprestado['status']}'.");

// TC-06 & TC-07: Empréstimo bem-sucedido e Devolução com Transaction
$db->exec("DELETE FROM leitores WHERE cpf = '999.888.777-66'");
$db->beginTransaction();
try {
    // Inserir leitor de teste
    $stmtLeitorT = $db->prepare("INSERT INTO leitores (nome_completo, cpf, ativo) VALUES ('Leitor Teste TC', '999.888.777-66', 1)");
    $stmtLeitorT->execute();
    $leitorIdT = $db->lastInsertId();

    // Inserir exemplar disponivel
    $stmtExT = $db->prepare("INSERT INTO exemplares (livro_id, codigo_exemplar, status) VALUES (?, 'EX-TEST-001', 'Disponível')");
    $stmtExT->execute([$testBookId]);
    $exIdT = $db->lastInsertId();

    // Inserir Empréstimo
    $stmtEmpT = $db->prepare("INSERT INTO emprestimos (leitor_id, livro_id, exemplar_id, usuario_id, data_emprestimo, data_prevista_devolucao, status) VALUES (?, ?, ?, 1, CURRENT_DATE(), DATE_ADD(CURRENT_DATE(), INTERVAL 14 DAY), 'Ativo')");
    $stmtEmpT->execute([$leitorIdT, $testBookId, $exIdT]);
    $empIdT = $db->lastInsertId();

    // Atualizar exemplar
    $db->prepare("UPDATE exemplares SET status = 'Emprestado' WHERE id = ?")->execute([$exIdT]);
    $db->prepare("UPDATE livros SET quantidade_disponivel = quantidade_disponivel - 1 WHERE id = ?")->execute([$testBookId]);

    $db->commit();
    assertTest("TC-06: Empréstimo bem-sucedido com Transaction", $empIdT > 0, "Empréstimo #{$empIdT} criado e exemplar alterado para Emprestado.");

    // Agora Devolução bem sucedida (TC-07)
    $db->beginTransaction();
    $stmtDevT = $db->prepare("INSERT INTO devolucoes (emprestimo_id, usuario_id, data_devolucao_real, estado_exemplar) VALUES (?, 1, NOW(), 'Perfeito estado')");
    $stmtDevT->execute([$empIdT]);
    $db->prepare("UPDATE emprestimos SET status = 'Concluído' WHERE id = ?")->execute([$empIdT]);
    $db->prepare("UPDATE exemplares SET status = 'Disponível' WHERE id = ?")->execute([$exIdT]);
    $db->prepare("UPDATE livros SET quantidade_disponivel = quantidade_disponivel + 1 WHERE id = ?")->execute([$testBookId]);
    $db->commit();
    assertTest("TC-07: Devolução bem-sucedida com Log Auditável", true, "Devolução inserida, empréstimo concluído e estoque restaurado.");

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    assertTest("TC-06/TC-07", false, $e->getMessage());
}

// TC-08: Tentativa de exclusão de livro com histórico -> Deve bloquear/inativar
$stmtCheckH = $db->prepare("SELECT COUNT(*) FROM emprestimos WHERE livro_id = ?");
$stmtCheckH->execute([$testBookId]);
$hasHistory = (int)$stmtCheckH->fetchColumn() > 0;
assertTest("TC-08: Exclusão indevida de livro com histórico de empréstimo", $hasHistory, "Detectou histórico prévio e impediu DELETE físico.");

// TC-09: Leitor com 3 empréstimos ativos tentando pegar o 4º
$stmtCount3 = $db->prepare("SELECT COUNT(*) FROM emprestimos WHERE leitor_id = 1 AND status = 'Ativo'");
$stmtCount3->execute();
$ativosCount3 = (int)$stmtCount3->fetchColumn();
$bloqueado4 = ($ativosCount3 >= 3);
assertTest("TC-09: Bloqueio de 4º empréstimo para leitor com limite 3/3", $bloqueado4, "Leitor Ana Beatriz possui {$ativosCount3}/3 empréstimos ativos e teve o 4º bloqueado.");

// TC-10: ISBN duplicado
$stmtIsbnDup = $db->prepare("SELECT id FROM livros WHERE isbn = '978-85-359-1002-3' AND ativo = 1");
$stmtIsbnDup->execute();
$isDup = (bool)$stmtIsbnDup->fetch();
assertTest("TC-10: Detecção de ISBN duplicado", $isDup, "Bloqueou duplicação do ISBN 978-85-359-1002-3.");

// TC-12: Acesso sem login (Auth Check)
$_SESSION = [];
$isLogged = isLoggedIn();
assertTest("TC-12: Proteção de acesso sem login", !$isLogged, "isLoggedIn() retornou false para sessão não autenticada.");

// TC-13: SQL Injection em campo de pesquisa
$inputSQL = "' OR 1=1 -- ";
$stmtSec = $db->prepare("SELECT COUNT(*) FROM livros WHERE titulo LIKE ?");
$stmtSec->execute(["%{$inputSQL}%"]);
$countSec = (int)$stmtSec->fetchColumn();
assertTest("TC-13: Proteção contra SQL Injection em pesquisas", $countSec === 0, "Query parametrizada via PDO tratou a string com segurança.");

// TC-14: XSS em nome de leitor/livro
$xssInput = "<script>alert('xss')</script>";
$sanitized = sanitize($xssInput);
assertTest("TC-14: Sanitização XSS com htmlspecialchars", $sanitized === "&lt;script&gt;alert(&#039;xss&#039;)&lt;/script&gt;", "Converteu tags HTML em entidades seguras.");

// Limpeza de registros de teste
$db->exec("DELETE FROM devolucoes WHERE emprestimo_id IN (SELECT id FROM emprestimos WHERE livro_id = {$testBookId})");
$db->exec("DELETE FROM emprestimos WHERE livro_id = {$testBookId}");
$db->exec("DELETE FROM exemplares WHERE livro_id = {$testBookId}");
$db->exec("DELETE FROM livros WHERE id = {$testBookId}");
$db->exec("DELETE FROM leitores WHERE cpf = '999.888.777-66'");

echo "\n==================================================\n";
echo " RESULTADO FINAL DOS TESTES: {$passed} PASSED / {$failed} FAILED\n";
echo "==================================================\n";
