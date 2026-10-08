<?php
/**
 * OpenBiblio - Recibo de Empréstimo Impresso
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Recibo de Empréstimo - OpenBiblio';
$pageHeaderTitle = 'Recibo de Empréstimo';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if (!$id) {
    setFlashMessage('danger', 'ID de empréstimo inválido.');
    header('Location: emprestimos.php');
    exit;
}

$db = getDBConnection();
$stmt = $db->prepare("
    SELECT e.*, 
           l.nome_completo AS leitor_nome, l.cpf AS leitor_cpf, l.email AS leitor_email, l.telefone AS leitor_telefone,
           b.titulo AS livro_titulo, b.autor AS livro_autor, b.isbn AS livro_isbn, b.capa AS livro_capa, b.editora AS livro_editora,
           ex.codigo_exemplar,
           u.nome AS operador_nome
    FROM emprestimos e
    JOIN leitores l ON e.leitor_id = l.id
    JOIN livros b ON e.livro_id = b.id
    JOIN exemplares ex ON e.exemplar_id = ex.id
    LEFT JOIN usuarios u ON e.usuario_id = u.id
    WHERE e.id = ? 
    LIMIT 1
");
$stmt->execute([$id]);
$recibo = $stmt->fetch();

if (!$recibo) {
    setFlashMessage('danger', 'Recibo de empréstimo não encontrado.');
    header('Location: emprestimos.php');
    exit;
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 btn-print-hide">
    <a href="emprestimos.php" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i> Voltar aos Empréstimos
    </a>
    <button onclick="window.print()" class="btn btn-gold px-4 fw-bold">
        <i class="bi bi-printer me-2"></i> Imprimir Recibo
    </button>
</div>

<!-- Container do Recibo Visual (Fidelidade à Referência) -->
<div class="receipt-box mb-5">
    <div class="receipt-title">
        <i class="bi bi-book-half me-2"></i> Recibo de Empréstimo #<?= sprintf('%05d', $recibo['id']) ?>
    </div>

    <!-- 1. Detalhes do Leitor -->
    <div class="mb-4">
        <div class="receipt-section-header">
            Detalhes do Leitor (Cadastrado)
        </div>
        <div class="row g-3">
            <div class="col-md-5">
                <small class="fw-bold text-muted d-block">Nome Completo:</small>
                <div class="receipt-field-box"><?= htmlspecialchars($recibo['leitor_nome']) ?></div>
            </div>
            <div class="col-md-3">
                <small class="fw-bold text-muted d-block">CPF:</small>
                <div class="receipt-field-box"><?= htmlspecialchars($recibo['leitor_cpf'] ?: 'Não informado') ?></div>
            </div>
            <div class="col-md-4">
                <small class="fw-bold text-muted d-block">E-mail / Contato:</small>
                <div class="receipt-field-box"><?= htmlspecialchars($recibo['leitor_email'] ?: $recibo['leitor_telefone'] ?: 'Sem contato') ?></div>
            </div>
        </div>
    </div>

    <!-- 2. Detalhes do Livro -->
    <div class="mb-4">
        <div class="receipt-section-header">
            Detalhes do Livro
        </div>
        <div class="bg-white p-3 rounded border border-warning d-flex flex-wrap align-items-center gap-3">
            <div style="width: 80px; height: 110px;" class="flex-shrink-0">
                <?php if (!empty($recibo['livro_capa']) && file_exists(__DIR__ . '/uploads/capas/' . $recibo['livro_capa'])): ?>
                    <img src="uploads/capas/<?= htmlspecialchars($recibo['livro_capa']) ?>" class="rounded img-fluid w-100 h-100" style="object-fit: cover;">
                <?php else: ?>
                    <div class="w-100 h-100 rounded bg-navy text-white d-flex align-items-center justify-content-center">
                        <i class="bi bi-book fs-2"></i>
                    </div>
                <?php endif; ?>
            </div>

            <div class="flex-grow-1">
                <h5 class="fw-bold text-navy m-0"><?= htmlspecialchars($recibo['livro_titulo']) ?></h5>
                <p class="text-muted m-0">Autor: <?= htmlspecialchars($recibo['livro_autor']) ?></p>
                <p class="text-muted m-0">Editora: <?= htmlspecialchars($recibo['livro_editora'] ?: '-') ?></p>
            </div>

            <div class="border-start ps-3">
                <small class="fw-bold text-muted d-block">Detalhes:</small>
                <div class="fw-semibold text-dark">ISBN: <?= htmlspecialchars($recibo['livro_isbn'] ?: 'N/A') ?></div>
                <div class="fw-semibold text-dark">Exemplar: <code><?= htmlspecialchars($recibo['codigo_exemplar']) ?></code></div>
            </div>
        </div>
    </div>

    <!-- 3. Datas de Movimentação -->
    <div>
        <div class="receipt-section-header">
            Datas de Movimentação
        </div>
        <div class="row g-3 text-center align-items-center">
            <div class="col-md-4">
                <small class="fw-bold text-muted d-block mb-1">Dia do Empréstimo</small>
                <div class="fs-5 fw-bold text-navy"><?= date('d/m/Y', strtotime($recibo['data_emprestimo'])) ?></div>
            </div>

            <div class="col-md-4">
                <small class="fw-bold text-muted d-block mb-1">Data Limite de Devolução</small>
                <div class="fs-5 fw-bold text-danger"><?= date('d/m/Y', strtotime($recibo['data_prevista_devolucao'])) ?></div>
                <span class="badge bg-secondary mt-1"><i class="bi bi-calendar-range me-1"></i> 2 semanas de prazo</span>
            </div>

            <div class="col-md-4">
                <small class="fw-bold text-muted d-block mb-1">Status do Empréstimo</small>
                <?php if ($recibo['status'] === 'Concluído'): ?>
                    <span class="badge badge-status badge-concluido fs-6">Concluído</span>
                <?php elseif ($recibo['status'] === 'Cancelado'): ?>
                    <span class="badge badge-status badge-cancelado fs-6">Cancelado</span>
                <?php else: ?>
                    <span class="badge badge-status badge-ativo fs-6">Ativo / Concedido</span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Rodapé do Recibo -->
    <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size: 0.8rem;">
        Empréstimo processado por <strong><?= htmlspecialchars($recibo['operador_nome'] ?: 'Administrador') ?></strong> em <?= date('d/m/Y \à\s H:i', strtotime($recibo['criado_em'])) ?>.<br>
        OpenBiblio - Sistema de Gerenciamento para Bibliotecas Comunitárias.
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
