<?php
/**
 * OpenBiblio - Gestão e Listagem de Empréstimos (Circulação)
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Empréstimos - OpenBiblio';
$pageHeaderTitle = 'Gestão de Empréstimos em Andamento';

$db = getDBConnection();

$busca = trim($_GET['busca'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$itensPorPagina = 10;
$offset = ($paginaAtual - 1) * $itensPorPagina;

$where = ["1=1"];
$params = [];

if (!empty($busca)) {
    $where[] = "(l.nome_completo LIKE ? OR b.titulo LIKE ? OR ex.codigo_exemplar LIKE ? OR e.id = ?)";
    $term = "%{$busca}%";
    $params = [$term, $term, $term, is_numeric($busca) ? (int)$busca : 0];
}

if ($statusFiltro === 'Ativo') {
    $where[] = "e.status = 'Ativo' AND e.data_prevista_devolucao >= CURRENT_DATE()";
} elseif ($statusFiltro === 'Atrasado') {
    $where[] = "e.status = 'Ativo' AND e.data_prevista_devolucao < CURRENT_DATE()";
} elseif ($statusFiltro === 'Concluído') {
    $where[] = "e.status = 'Concluído'";
} elseif ($statusFiltro === 'Cancelado') {
    $where[] = "e.status = 'Cancelado'";
}

$whereSql = implode(" AND ", $where);

// Total de empréstimos
$stmtTotal = $db->prepare("
    SELECT COUNT(*) 
    FROM emprestimos e
    JOIN leitores l ON e.leitor_id = l.id
    JOIN livros b ON e.livro_id = b.id
    JOIN exemplares ex ON e.exemplar_id = ex.id
    WHERE {$whereSql}
");
$stmtTotal->execute($params);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, ceil($totalRegistros / $itensPorPagina));

// Consulta principal
$query = "
    SELECT e.*, 
           l.nome_completo AS leitor_nome, l.cpf AS leitor_cpf, l.telefone AS leitor_telefone,
           b.titulo AS livro_titulo, b.autor AS livro_autor, b.isbn AS livro_isbn,
           ex.codigo_exemplar
    FROM emprestimos e
    JOIN leitores l ON e.leitor_id = l.id
    JOIN livros b ON e.livro_id = b.id
    JOIN exemplares ex ON e.exemplar_id = ex.id
    WHERE {$whereSql}
    ORDER BY e.id DESC
    LIMIT {$itensPorPagina} OFFSET {$offset}
";
$stmtEmprestimos = $db->prepare($query);
$stmtEmprestimos->execute($params);
$emprestimos = $stmtEmprestimos->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="font-outfit fw-bold m-0 text-navy">Circulação / Controle de Empréstimos</h2>
        <p class="text-muted m-0">Acompanhe prazos, renovações, devoluções e alertas de atraso.</p>
    </div>
    <a href="emprestimo_novo.php" class="btn btn-gold">
        <i class="bi bi-plus-lg me-1"></i> + Novo Empréstimo
    </a>
</div>

<!-- Filtros de Pesquisa -->
<div class="card-ob mb-4">
    <div class="card-ob-body py-3">
        <form method="GET" action="emprestimos.php" class="row g-3 align-items-center">
            <div class="col-md-6 col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="busca" class="form-control" placeholder="Buscar por Leitor, Livro, Exemplar ou ID..." value="<?= htmlspecialchars($busca) ?>">
                </div>
            </div>

            <div class="col-md-4 col-lg-4">
                <div class="btn-group w-100" role="group">
                    <a href="emprestimos.php?status=&busca=<?= urlencode($busca) ?>" class="btn btn-sm btn-outline-navy <?= empty($statusFiltro) ? 'active' : '' ?>">Todos</a>
                    <a href="emprestimos.php?status=Ativo&busca=<?= urlencode($busca) ?>" class="btn btn-sm btn-outline-success <?= $statusFiltro === 'Ativo' ? 'active' : '' ?>">Ativos</a>
                    <a href="emprestimos.php?status=Atrasado&busca=<?= urlencode($busca) ?>" class="btn btn-sm btn-outline-danger <?= $statusFiltro === 'Atrasado' ? 'active' : '' ?>">Atrasados</a>
                    <a href="emprestimos.php?status=Concluído&busca=<?= urlencode($busca) ?>" class="btn btn-sm btn-outline-secondary <?= $statusFiltro === 'Concluído' ? 'active' : '' ?>">Concluídos</a>
                </div>
            </div>

            <div class="col-md-2 col-lg-3 text-end">
                <button type="submit" class="btn btn-navy w-100"><i class="bi bi-filter"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Empréstimos -->
<div class="card-ob">
    <div class="card-ob-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-ob mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Leitor</th>
                        <th>Obra / Exemplar</th>
                        <th>Data Empréstimo</th>
                        <th>Prazo Devolução</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($emprestimos)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nenhum empréstimo encontrado para os filtros selecionados.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($emprestimos as $emp): 
                            $isAtrasado = ($emp['status'] === 'Ativo' && strtotime($emp['data_prevista_devolucao']) < strtotime(date('Y-m-d')));
                        ?>
                            <tr class="<?= $isAtrasado ? 'table-danger' : '' ?>">
                                <td><strong>#<?= $emp['id'] ?></strong></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($emp['leitor_nome']) ?></div>
                                    <small class="text-muted"><i class="bi bi-telephone me-1"></i><?= htmlspecialchars($emp['leitor_telefone'] ?: 'Sem fone') ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($emp['livro_titulo']) ?></div>
                                    <small class="text-muted"><i class="bi bi-upc-scan me-1"></i>Código: <code><?= htmlspecialchars($emp['codigo_exemplar']) ?></code></small>
                                </td>
                                <td><?= date('d/m/Y', strtotime($emp['data_emprestimo'])) ?></td>
                                <td>
                                    <strong><?= date('d/m/Y', strtotime($emp['data_prevista_devolucao'])) ?></strong>
                                    <?php if ($isAtrasado): ?>
                                        <div class="badge bg-danger text-white d-inline-block mt-1" style="font-size: 0.7rem;">
                                            <i class="bi bi-exclamation-octagon-fill me-1"></i> ATRASADO
                                        </div>
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
                                    <div class="btn-group btn-group-sm">
                                        <a href="recibo.php?id=<?= $emp['id'] ?>" class="btn btn-outline-secondary" title="Ver Recibo">
                                            <i class="bi bi-receipt"></i>
                                        </a>

                                        <?php if ($emp['status'] === 'Ativo'): ?>
                                            <!-- Botão Dar Baixa (Devolver) -->
                                            <button type="button" class="btn btn-success text-white" data-bs-toggle="modal" data-bs-target="#modalDevolucaoList<?= $emp['id'] ?>" title="Registrar Devolução">
                                                <i class="bi bi-box-arrow-in-down"></i> Devolver
                                            </button>

                                            <!-- Botão Renovar (Prorrogar Prazo) -->
                                            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalRenovar<?= $emp['id'] ?>" title="Renovar / Prorrogar Prazo">
                                                <i class="bi bi-arrow-repeat"></i> Renovar
                                            </button>

                                            <!-- Botão Cancelar -->
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalCancelarEmp<?= $emp['id'] ?>" title="Cancelar Empréstimo">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal Devolução -->
                            <?php if ($emp['status'] === 'Ativo'): ?>
                                <div class="modal fade" id="modalDevolucaoList<?= $emp['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content text-start">
                                            <form action="devolucao_registrar.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="emprestimo_id" value="<?= $emp['id'] ?>">
                                                
                                                <div class="modal-header bg-navy text-white">
                                                    <h5 class="modal-title font-outfit"><i class="bi bi-journal-check text-warning me-2"></i> Registrar Devolução</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="alert alert-light border">
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
                                                        <textarea name="observacoes" class="form-control" rows="3" placeholder="Observações sobre o estado do exemplar..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-success fw-bold"><i class="bi bi-check-circle me-1"></i> Confirmar Baixa</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Renovar -->
                                <div class="modal fade" id="modalRenovar<?= $emp['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content text-start">
                                            <form action="emprestimo_editar.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="id" value="<?= $emp['id'] ?>">

                                                <div class="modal-header bg-navy text-white">
                                                    <h5 class="modal-title font-outfit"><i class="bi bi-arrow-repeat text-warning me-2"></i> Renovar Empréstimo #<?= $emp['id'] ?></h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Prorrogue o prazo limite de devolução para a obra <strong><?= htmlspecialchars($emp['livro_titulo']) ?></strong>.</p>
                                                    
                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Nova Data Prevista de Devolução *</label>
                                                        <input type="date" name="data_prevista_devolucao" class="form-control" value="<?= date('Y-m-d', strtotime($emp['data_prevista_devolucao'] . ' +14 days')) ?>" required>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label fw-bold">Observações de Renovação</label>
                                                        <textarea name="observacoes" class="form-control" rows="2" placeholder="Motivo da renovação..."><?= htmlspecialchars($emp['observacoes'] ?? '') ?></textarea>
                                                    </div>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                    <button type="submit" class="btn btn-primary fw-bold">Confirmar Renovação</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>

                                <!-- Modal Cancelar Empréstimo -->
                                <div class="modal fade" id="modalCancelarEmp<?= $emp['id'] ?>" tabindex="-1" aria-hidden="true">
                                    <div class="modal-dialog modal-dialog-centered">
                                        <div class="modal-content text-start">
                                            <form action="emprestimo_cancelar.php" method="POST">
                                                <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                                <input type="hidden" name="id" value="<?= $emp['id'] ?>">

                                                <div class="modal-header bg-danger text-white">
                                                    <h5 class="modal-title font-outfit"><i class="bi bi-x-circle-fill me-2"></i> Cancelar Registro de Empréstimo</h5>
                                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Tem certeza de que deseja cancelar este empréstimo inserido incorretamente?</p>
                                                    <div class="alert alert-warning">
                                                        <strong>Livro:</strong> <?= htmlspecialchars($emp['livro_titulo']) ?><br>
                                                        <strong>Leitor:</strong> <?= htmlspecialchars($emp['leitor_nome']) ?>
                                                    </div>
                                                    <small class="text-muted">O status será alterado para Cancelado e o exemplar retornará à disponibilidade física de forma segura.</small>
                                                </div>
                                                <div class="modal-footer bg-light">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Manter Ativo</button>
                                                    <button type="submit" class="btn btn-danger fw-bold">Confirmar Cancelamento</button>
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

<!-- Paginação -->
<?php if ($totalPaginas > 1): ?>
    <nav class="mt-4">
        <ul class="pagination justify-content-center">
            <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                <li class="page-item <?= $p === $paginaAtual ? 'active' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $p ?>&busca=<?= urlencode($busca) ?>&status=<?= urlencode($statusFiltro) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
