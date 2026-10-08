<?php
/**
 * OpenBiblio - Novo Empréstimo de Livro
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Novo Empréstimo - OpenBiblio';
$pageHeaderTitle = 'Concessão de Empréstimo';

$db = getDBConnection();

// Buscar todos os leitores ativos com a contagem de empréstimos ativos via JOIN otimizado
$stmtLeitores = $db->query("
    SELECT l.id, l.nome_completo, l.cpf, COUNT(e.id) AS ativos_count
    FROM leitores l
    LEFT JOIN emprestimos e ON l.id = e.leitor_id AND e.status = 'Ativo'
    WHERE l.ativo = 1
    GROUP BY l.id, l.nome_completo, l.cpf
    ORDER BY l.nome_completo ASC
");
$leitores = $stmtLeitores->fetchAll();

// Buscar livros que possuem pelo menos 1 exemplar disponível
$stmtLivros = $db->query("
    SELECT b.id, b.titulo, b.autor, b.isbn, b.quantidade_disponivel
    FROM livros b
    WHERE b.ativo = 1 AND b.quantidade_disponivel > 0 AND b.status = 'Disponível'
    ORDER BY b.titulo ASC
");
$livros = $stmtLivros->fetchAll();

$dataHoje = date('Y-m-d');
$dataPrevistaDevolucao = date('Y-m-d', strtotime('+14 days')); // RN-03: 14 dias corridos

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h2 class="font-outfit fw-bold m-0 text-navy">Registrar Saída / Novo Empréstimo</h2>
        <p class="text-muted m-0">Selecione o leitor, a obra e o exemplar físico a ser concedido.</p>
    </div>
    <a href="emprestimos.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Voltar aos Empréstimos
    </a>
</div>

<div class="card-ob">
    <div class="card-ob-header">
        <span><i class="bi bi-arrow-left-right me-2 text-warning"></i> Formulario de Concessão de Empréstimo</span>
    </div>
    <div class="card-ob-body">
        <form action="emprestimo_salvar.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">

            <!-- 1. Leitor -->
            <div class="mb-4 p-3 rounded bg-light border">
                <label for="selectLeitorEmprestimo" class="form-label fw-bold d-flex align-items-center justify-content-between">
                    <span>1. Selecione o Leitor (Tomador) *</span>
                    <span id="leitorStatusBadge" class="badge bg-secondary">Empréstimos ativos: 0/3</span>
                </label>
                <select name="leitor_id" id="selectLeitorEmprestimo" class="form-select form-select-lg" required>
                    <option value="">-- Selecione o Leitor --</option>
                    <?php foreach ($leitores as $l): ?>
                        <option value="<?= $l['id'] ?>" data-ativos="<?= $l['ativos_count'] ?>">
                            <?= htmlspecialchars($l['nome_completo']) ?> (CPF: <?= htmlspecialchars($l['cpf'] ?: 'N/A') ?>) - <?= $l['ativos_count'] ?>/3 ativos
                        </option>
                    <?php endforeach; ?>
                </select>
                <small class="text-muted">Nota (RN-04): Cada leitor pode possuir no máximo 3 empréstimos ativos simultaneamente.</small>
            </div>

            <!-- 2. Livro e Exemplar -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="selectLivroEmprestimo" class="form-label fw-bold">2. Selecione a Obra / Livro *</label>
                    <select name="livro_id" id="selectLivroEmprestimo" class="form-select" required>
                        <option value="">-- Selecione o Livro --</option>
                        <?php foreach ($livros as $b): ?>
                            <option value="<?= $b['id'] ?>">
                                <?= htmlspecialchars($b['titulo']) ?> - <?= htmlspecialchars($b['autor']) ?> (<?= $b['quantidade_disponivel'] ?> disponíveis)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-6">
                    <label for="selectExemplarEmprestimo" class="form-label fw-bold">3. Selecione o Exemplar Físico *</label>
                    <select name="exemplar_id" id="selectExemplarEmprestimo" class="form-select" disabled required>
                        <option value="">Selecione primeiro um livro acima...</option>
                    </select>
                </div>
            </div>

            <!-- 3. Datas e Prazos -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label for="data_emprestimo" class="form-label fw-bold">Data da Retirada / Concessão *</label>
                    <input type="date" name="data_emprestimo" id="data_emprestimo" class="form-control" value="<?= $dataHoje ?>" required>
                </div>

                <div class="col-md-6">
                    <label for="data_prevista_devolucao" class="form-label fw-bold">Data Limite Prevista para Devolução *</label>
                    <input type="date" name="data_prevista_devolucao" id="data_prevista_devolucao" class="form-control" value="<?= $dataPrevistaDevolucao ?>" required>
                    <small class="text-muted">Prazo padrão (RN-03): 14 dias corridos a partir da data de concessão.</small>
                </div>
            </div>

            <!-- 4. Observações -->
            <div class="mb-4">
                <label for="observacoes" class="form-label fw-bold">Observações de Concessão</label>
                <textarea name="observacoes" id="observacoes" class="form-control" rows="3" placeholder="Insira eventuais notas ou ressalvas sobre este empréstimo..."></textarea>
            </div>

            <div class="d-flex justify-content-end gap-2 border-top pt-3">
                <a href="emprestimos.php" class="btn btn-secondary">Cancelar</a>
                <button type="submit" id="btnSubmitEmprestimo" class="btn btn-gold fw-bold py-2 px-4">
                    <i class="bi bi-check-circle me-1"></i> Confirmar e Registrar Empréstimo
                </button>
            </div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
