<?php
/**
 * OpenBiblio - Histórico de Devoluções (Auditoria Imutável)
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Histórico de Devoluções - OpenBiblio';
$pageHeaderTitle = 'Log de Auditoria de Devoluções';

$db = getDBConnection();

$busca = trim($_GET['busca'] ?? '');
$dataInicio = trim($_GET['data_inicio'] ?? '');
$dataFim = trim($_GET['data_fim'] ?? '');
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$itensPorPagina = 10;
$offset = ($paginaAtual - 1) * $itensPorPagina;

$where = ["1=1"];
$params = [];

if (!empty($busca)) {
    $where[] = "(l.nome_completo LIKE ? OR b.titulo LIKE ? OR ex.codigo_exemplar LIKE ? OR d.id = ? OR e.id = ?)";
    $term = "%{$busca}%";
    $num = is_numeric($busca) ? (int)$busca : 0;
    $params = [$term, $term, $term, $num, $num];
}

if (!empty($dataInicio)) {
    $where[] = "DATE(d.data_devolucao_real) >= ?";
    $params[] = $dataInicio;
}

if (!empty($dataFim)) {
    $where[] = "DATE(d.data_devolucao_real) <= ?";
    $params[] = $dataFim;
}

$whereSql = implode(" AND ", $where);

// Total de devoluções
$stmtTotal = $db->prepare("
    SELECT COUNT(*) 
    FROM devolucoes d
    JOIN emprestimos e ON d.emprestimo_id = e.id
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
    SELECT d.*, 
           e.data_emprestimo, e.data_prevista_devolucao,
           l.nome_completo AS leitor_nome,
           b.titulo AS livro_titulo,
           ex.codigo_exemplar,
           u.nome AS operador_nome
    FROM devolucoes d
    JOIN emprestimos e ON d.emprestimo_id = e.id
    JOIN leitores l ON e.leitor_id = l.id
    JOIN livros b ON e.livro_id = b.id
    JOIN exemplares ex ON e.exemplar_id = ex.id
    LEFT JOIN usuarios u ON d.usuario_id = u.id
    WHERE {$whereSql}
    ORDER BY d.id DESC
    LIMIT {$itensPorPagina} OFFSET {$offset}
";
$stmtDev = $db->prepare($query);
$stmtDev->execute($params);
$devolucoes = $stmtDev->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="font-outfit fw-bold m-0 text-navy">Histórico Imutável de Devoluções</h2>
        <p class="text-muted m-0">Registro histórico e auditável de baixa física de exemplares.</p>
    </div>
</div>

<!-- Banner de Imutabilidade Auditável -->
<div class="alert alert-warning d-flex align-items-center mb-4 shadow-sm border-warning" role="alert">
    <i class="bi text-warning fs-3 me-3 bi-shield-lock-fill"></i>
    <div>
        <strong>Aviso de Auditoria:</strong> Este histórico é auditável e imutável. Os registros de devolução concluídos não podem ser excluídos ou alterados no banco de dados.
    </div>
</div>

<!-- Filtros de Pesquisa -->
<div class="card-ob mb-4">
    <div class="card-ob-body py-3">
        <form method="GET" action="devolucoes.php" class="row g-3 align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="busca" class="form-control" placeholder="Buscar por Leitor, Livro, Exemplar ou ID..." value="<?= htmlspecialchars($busca) ?>">
                </div>
            </div>

            <div class="col-md-3">
                <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($dataInicio) ?>" placeholder="Data Inicial">
            </div>

            <div class="col-md-3">
                <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($dataFim) ?>" placeholder="Data Final">
            </div>

            <div class="col-md-2">
                <button type="submit" class="btn btn-navy w-100"><i class="bi bi-filter"></i> Filtrar</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Devoluções -->
<div class="card-ob">
    <div class="card-ob-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-ob mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID Devolução</th>
                        <th>Empréstimo</th>
                        <th>Leitor</th>
                        <th>Obra / Exemplar</th>
                        <th>Data Empréstimo</th>
                        <th>Data Devolução Real</th>
                        <th>Estado do Exemplar</th>
                        <th>Operador</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($devolucoes)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Nenhum registro de devolução encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($devolucoes as $dev): ?>
                            <tr>
                                <td><strong>#DEV-<?= sprintf('%05d', $dev['id']) ?></strong></td>
                                <td><a href="recibo.php?id=<?= $dev['emprestimo_id'] ?>" class="text-navy font-monospace">#<?= $dev['emprestimo_id'] ?></a></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($dev['leitor_nome']) ?></div>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= htmlspecialchars($dev['livro_titulo']) ?></div>
                                    <small class="text-muted">Exemplar: <code><?= htmlspecialchars($dev['codigo_exemplar']) ?></code></small>
                                </td>
                                <td><?= date('d/m/Y', strtotime($dev['data_emprestimo'])) ?></td>
                                <td>
                                    <strong class="text-success"><i class="bi bi-check-circle me-1"></i><?= date('d/m/Y H:i', strtotime($dev['data_devolucao_real'])) ?></strong>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($dev['estado_exemplar']) ?>
                                    </span>
                                    <?php if (!empty($dev['observacoes'])): ?>
                                        <br><small class="text-muted"><em>"<?= htmlspecialchars($dev['observacoes']) ?>"</em></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small class="text-muted"><i class="bi bi-person-badge me-1"></i><?= htmlspecialchars($dev['operador_nome'] ?: 'Sistema') ?></small>
                                </td>
                            </tr>
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
                    <a class="page-link" href="?pagina=<?= $p ?>&busca=<?= urlencode($busca) ?>&data_inicio=<?= urlencode($dataInicio) ?>&data_fim=<?= urlencode($dataFim) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
