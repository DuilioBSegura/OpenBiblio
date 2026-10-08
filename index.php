<?php
/**
 * OpenBiblio - Painel / Dashboard Principal
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Dashboard - OpenBiblio';
$pageHeaderTitle = 'Painel de Controle / Dashboard';

$db = getDBConnection();

// Queries para Indicadores (Cards)
$totalLivros = (int) $db->query("SELECT COUNT(*) FROM livros WHERE ativo = 1")->fetchColumn();
$totalExemplares = (int) $db->query("SELECT COUNT(*) FROM exemplares")->fetchColumn();
$exemplaresDisponiveis = (int) $db->query("SELECT COUNT(*) FROM exemplares WHERE status = 'Disponível'")->fetchColumn();
$exemplaresEmprestados = (int) $db->query("SELECT COUNT(*) FROM exemplares WHERE status = 'Emprestado'")->fetchColumn();
$leitoresAtivos = (int) $db->query("SELECT COUNT(*) FROM leitores WHERE ativo = 1")->fetchColumn();
$emprestimosHoje = (int) $db->query("SELECT COUNT(*) FROM emprestimos WHERE data_emprestimo = CURRENT_DATE()")->fetchColumn();
$devolucoesHoje = (int) $db->query("SELECT COUNT(*) FROM devolucoes WHERE DATE(data_devolucao_real) = CURRENT_DATE()")->fetchColumn();
$emprestimosAtrasados = (int) $db->query("SELECT COUNT(*) FROM emprestimos WHERE status = 'Ativo' AND data_prevista_devolucao < CURRENT_DATE()")->fetchColumn();

// Query: Últimos Empréstimos
$stmtUltimos = $db->query("
    SELECT e.id, e.data_emprestimo, e.data_prevista_devolucao, e.status,
           l.nome_completo AS leitor_nome,
           b.titulo AS livro_titulo, b.autor AS livro_autor,
           ex.codigo_exemplar
    FROM emprestimos e
    JOIN leitores l ON e.leitor_id = l.id
    JOIN livros b ON e.livro_id = b.id
    JOIN exemplares ex ON e.exemplar_id = ex.id
    ORDER BY e.id DESC
    LIMIT 6
");
$ultimosEmprestimos = $stmtUltimos->fetchAll();

// Query: Livros Mais Procurados
$stmtMaisProcurados = $db->query("
    SELECT b.id, b.titulo, b.autor, b.categoria, b.capa, COUNT(e.id) AS total_emprestimos
    FROM livros b
    LEFT JOIN emprestimos e ON b.id = e.livro_id
    WHERE b.ativo = 1
    GROUP BY b.id
    ORDER BY total_emprestimos DESC, b.titulo ASC
    LIMIT 5
");
$livrosMaisProcurados = $stmtMaisProcurados->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<!-- Grid de Indicadores KPI -->
<div class="row g-3 mb-4">
    <!-- Total de Livros -->
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-value"><?= $totalLivros ?></div>
                <div class="stat-label">Total de Livros</div>
            </div>
            <div class="stat-icon navy">
                <i class="bi bi-book"></i>
            </div>
        </div>
    </div>

    <!-- Total de Exemplares -->
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-value"><?= $totalExemplares ?></div>
                <div class="stat-label">Exemplares</div>
            </div>
            <div class="stat-icon gold">
                <i class="bi bi-journal-bookmark"></i>
            </div>
        </div>
    </div>

    <!-- Exemplares Disponíveis -->
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-value"><?= $exemplaresDisponiveis ?></div>
                <div class="stat-label">Disponíveis</div>
            </div>
            <div class="stat-icon green">
                <i class="bi bi-check-circle"></i>
            </div>
        </div>
    </div>

    <!-- Exemplares Emprestados -->
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-value"><?= $exemplaresEmprestados ?></div>
                <div class="stat-label">Emprestados</div>
            </div>
            <div class="stat-icon blue">
                <i class="bi bi-arrow-right-circle"></i>
            </div>
        </div>
    </div>

    <!-- Leitores Ativos -->
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-value"><?= $leitoresAtivos ?></div>
                <div class="stat-label">Leitores Ativos</div>
            </div>
            <div class="stat-icon yellow">
                <i class="bi bi-people"></i>
            </div>
        </div>
    </div>

    <!-- Empréstimos Hoje -->
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-value"><?= $emprestimosHoje ?></div>
                <div class="stat-label">Empréstimos Hoje</div>
            </div>
            <div class="stat-icon blue">
                <i class="bi bi-calendar-event"></i>
            </div>
        </div>
    </div>

    <!-- Devoluções Hoje -->
    <div class="col-6 col-md-3">
        <div class="stat-card">
            <div class="stat-info">
                <div class="stat-value"><?= $devolucoesHoje ?></div>
                <div class="stat-label">Devoluções Hoje</div>
            </div>
            <div class="stat-icon green">
                <i class="bi bi-box-arrow-in-down"></i>
            </div>
        </div>
    </div>

    <!-- Empréstimos Atrasados -->
    <div class="col-6 col-md-3">
        <div class="stat-card" style="border-left: 4px solid var(--ob-danger);">
            <div class="stat-info">
                <div class="stat-value text-danger"><?= $emprestimosAtrasados ?></div>
                <div class="stat-label text-danger">Atrasados</div>
            </div>
            <div class="stat-icon red">
                <i class="bi bi-exclamation-octagon"></i>
            </div>
        </div>
    </div>
</div>

<!-- Seção de Ações Rápidas -->
<div class="d-flex flex-wrap gap-2 mb-4">
    <a href="emprestimo_novo.php" class="btn btn-gold">
        <i class="bi bi-plus-lg me-1"></i> Novo Empréstimo
    </a>
    <a href="livro_cadastrar.php" class="btn btn-navy">
        <i class="bi bi-journal-plus me-1"></i> Cadastrar Livro
    </a>
    <a href="leitor_cadastrar.php" class="btn btn-outline-dark bg-white">
        <i class="bi bi-person-plus me-1"></i> Cadastrar Leitor
    </a>
</div>

<div class="row g-4 mb-4">
    <!-- Gráfico de Visão Geral do Acervo -->
    <div class="col-lg-8">
        <div class="card-ob h-100">
            <div class="card-ob-header">
                <span><i class="bi bi-pie-chart me-2 text-warning"></i> Distribuição dos Exemplares no Acervo</span>
            </div>
            <div class="card-ob-body d-flex align-items-center justify-content-center">
                <div style="width: 100%; max-height: 280px; position: relative;">
                    <canvas id="chartAcervo"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Painel de Destaques / Livros Mais Procurados -->
    <div class="col-lg-4">
        <div class="card-ob h-100">
            <div class="card-ob-header gold">
                <span><i class="bi bi-star me-2"></i> Livros Mais Procurados</span>
            </div>
            <div class="card-ob-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($livrosMaisProcurados)): ?>
                        <li class="list-group-item text-muted text-center py-4">Nenhum livro registrado.</li>
                    <?php else: ?>
                        <?php foreach ($livrosMaisProcurados as $index => $livro): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center py-3">
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-secondary rounded-pill fs-6"><?= $index + 1 ?></span>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size: 0.95rem;"><?= htmlspecialchars($livro['titulo']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($livro['autor']) ?></small>
                                    </div>
                                </div>
                                <span class="badge bg-primary rounded-pill"><?= $livro['total_emprestimos'] ?> empréstimos</span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Tabela de Últimos Empréstimos -->
<div class="card-ob">
    <div class="card-ob-header d-flex justify-content-between align-items-center">
        <span><i class="bi bi-arrow-left-right me-2 text-warning"></i> Últimos Empréstimos Registrados</span>
        <a href="emprestimos.php" class="btn btn-sm btn-outline-light">Ver Todos</a>
    </div>
    <div class="card-ob-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-ob mb-0">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Leitor</th>
                        <th>Livro / Exemplar</th>
                        <th>Data Saída</th>
                        <th>Prazo Previsto</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($ultimosEmprestimos)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nenhum empréstimo registrado no momento.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($ultimosEmprestimos as $emp): 
                            $isAtrasado = ($emp['status'] === 'Ativo' && strtotime($emp['data_prevista_devolucao']) < strtotime(date('Y-m-d')));
                        ?>
                            <tr>
                                <td><strong>#<?= $emp['id'] ?></strong></td>
                                <td>
                                    <i class="bi bi-person text-muted me-1"></i>
                                    <?= htmlspecialchars($emp['leitor_nome']) ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= htmlspecialchars($emp['livro_titulo']) ?></div>
                                    <small class="text-muted"><i class="bi bi-upc-scan"></i> <?= htmlspecialchars($emp['codigo_exemplar']) ?></small>
                                </td>
                                <td><?= date('d/m/Y', strtotime($emp['data_emprestimo'])) ?></td>
                                <td>
                                    <?= date('d/m/Y', strtotime($emp['data_prevista_devolucao'])) ?>
                                    <?php if ($isAtrasado): ?>
                                        <span class="badge bg-danger ms-1" style="font-size: 0.7rem;">VENCIDO</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($emp['status'] === 'Concluído'): ?>
                                        <span class="badge badge-status badge-concluido"><i class="bi bi-check-all"></i> Concluído</span>
                                    <?php elseif ($emp['status'] === 'Cancelado'): ?>
                                        <span class="badge badge-status badge-cancelado">Cancelado</span>
                                    <?php elseif ($isAtrasado): ?>
                                        <span class="badge badge-status badge-atrasado"><i class="bi bi-exclamation-triangle"></i> Atrasado</span>
                                    <?php else: ?>
                                        <span class="badge badge-status badge-ativo"><i class="bi bi-clock"></i> Ativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="recibo.php?id=<?= $emp['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Ver Recibo">
                                        <i class="bi bi-receipt"></i> Recibo
                                    </a>
                                    <?php if ($emp['status'] === 'Ativo'): ?>
                                        <button type="button" class="btn btn-sm btn-success text-white" data-bs-toggle="modal" data-bs-target="#modalDevolucao<?= $emp['id'] ?>">
                                            <i class="bi bi-box-arrow-in-down"></i> Devolver
                                        </button>
                                    <?php endif; ?>
                                </td>
                            </tr>

                            <!-- Modal de Devolução Rápida para empréstimos ativos da tabela -->
                            <?php if ($emp['status'] === 'Ativo'): ?>
                                <div class="modal fade" id="modalDevolucao<?= $emp['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content">
                                            <form action="devolucao_registrar.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="emprestimo_id" value="<?= $emp['id'] ?>">
                                                
                                                <div class="modal-header bg-navy text-white">
                                                    <h5 class="modal-title font-outfit"><i class="bi bi-journal-check text-warning me-2"></i> Registrar Devolução</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body text-start">
                                                    <div class="alert alert-info">
                                                        <strong>Livro:</strong> <?= htmlspecialchars($emp['livro_titulo']) ?><br>
                                                        <strong>Leitor:</strong> <?= htmlspecialchars($emp['leitor_nome']) ?><br>
                                                        <strong>Exemplar:</strong> <?= htmlspecialchars($emp['codigo_exemplar']) ?>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Estado do Exemplar Devolvido *</label>
                                                        <select name="estado_exemplar" class="form-select" required>
                                                            <option value="Perfeito estado" selected>Perfeito estado</option>
                                                            <option value="Pequena avaria">Pequena avaria</option>
                                                            <option value="Danificado">Danificado</option>
                                                            <option value="Em manutenção">Em manutenção</option>
                                                            <option value="Extraviado">Extraviado</option>
                                                        </select>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Observações de Entrega</label>
                                                        <textarea name="observacoes" class="form-control" rows="3" placeholder="Insira observações opcionais sobre a devolução..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-success fw-bold">
                                                        <i class="bi bi-check-circle me-1"></i> Dar Baixa e Sincronizar Estoque
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>

                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Script do Gráfico Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ctx = document.getElementById('chartAcervo').getContext('2d');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['Disponíveis', 'Emprestados', 'Atrasados'],
            datasets: [{
                data: [<?= $exemplaresDisponiveis ?>, <?= $exemplaresEmprestados - $emprestimosAtrasados ?>, <?= $emprestimosAtrasados ?>],
                backgroundColor: ['#10b981', '#3b82f6', '#ef4444'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { font: { family: 'Plus Jakarta Sans', size: 13 } }
                }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
