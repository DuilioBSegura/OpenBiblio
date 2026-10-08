<?php
/**
 * OpenBiblio - Catálogo e Listagem de Livros (Acervo)
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Acervo de Livros - OpenBiblio';
$pageHeaderTitle = 'Gestão do Acervo / Livros';

$db = getDBConnection();

// Parâmetros de Pesquisa e Filtros
$busca = trim($_GET['busca'] ?? '');
$statusFiltro = trim($_GET['status'] ?? '');
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$itensPorPagina = 10;
$offset = ($paginaAtual - 1) * $itensPorPagina;

// Construção da consulta SQL parametrizada
$where = ["b.ativo = 1"];
$params = [];

if (!empty($busca)) {
    $where[] = "(b.titulo LIKE ? OR b.autor LIKE ? OR b.isbn LIKE ? OR b.id = ?)";
    $term = "%{$busca}%";
    $params[] = $term;
    $params[] = $term;
    $params[] = $term;
    $params[] = is_numeric($busca) ? (int)$busca : 0;
}

if (!empty($statusFiltro)) {
    $where[] = "b.status = ?";
    $params[] = $statusFiltro;
}

$whereSql = implode(" AND ", $where);

// Total de registros para paginação
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM livros b WHERE {$whereSql}");
$stmtTotal->execute($params);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, ceil($totalRegistros / $itensPorPagina));

// Consulta dos Livros
$query = "
    SELECT b.*, 
           (SELECT COUNT(*) FROM exemplares ex WHERE ex.livro_id = b.id AND ex.status != 'Inativo') AS total_exemplares_reais,
           (SELECT COUNT(*) FROM exemplares ex WHERE ex.livro_id = b.id AND ex.status = 'Disponível') AS disponiveis_reais
    FROM livros b
    WHERE {$whereSql}
    ORDER BY b.id DESC
    LIMIT {$itensPorPagina} OFFSET {$offset}
";
$stmtLivros = $db->prepare($query);
$stmtLivros->execute($params);
$livros = $stmtLivros->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="font-outfit fw-bold m-0 text-navy">Catálogo do Acervo Bibliográfico</h2>
        <p class="text-muted m-0">Gerencie obras, edições, exemplares físicos e disponibilidades.</p>
    </div>
    <a href="livro_cadastrar.php" class="btn btn-gold">
        <i class="bi bi-journal-plus me-1"></i> + Novo Livro
    </a>
</div>

<!-- Card de Filtros de Pesquisa -->
<div class="card-ob mb-4">
    <div class="card-ob-body py-3">
        <form method="GET" action="livros.php" class="row g-3 align-items-center">
            <div class="col-md-6 col-lg-5">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="busca" class="form-control" placeholder="Pesquisar por Título, Autor, ISBN ou ID..." value="<?= htmlspecialchars($busca) ?>">
                </div>
            </div>

            <div class="col-md-4 col-lg-3">
                <select name="status" class="form-select">
                    <option value="">-- Todos os Status --</option>
                    <option value="Disponível" <?= $statusFiltro === 'Disponível' ? 'selected' : '' ?>>Disponível</option>
                    <option value="Indisponível" <?= $statusFiltro === 'Indisponível' ? 'selected' : '' ?>>Indisponível</option>
                </select>
            </div>

            <div class="col-md-2 col-lg-2">
                <button type="submit" class="btn btn-navy w-100">
                    <i class="bi bi-filter me-1"></i> Filtrar
                </button>
            </div>

            <?php if (!empty($busca) || !empty($statusFiltro)): ?>
                <div class="col-md-2 col-lg-2">
                    <a href="livros.php" class="btn btn-outline-secondary w-100">Limpar</a>
                </div>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Tabela de Livros -->
<div class="card-ob">
    <div class="card-ob-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-ob mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Capa</th>
                        <th>Título / Autor</th>
                        <th>ISBN</th>
                        <th>Editora / Ano</th>
                        <th>Exemplares</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($livros)): ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">Nenhum registro encontrado no acervo.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($livros as $livro): ?>
                            <tr>
                                <td><strong>#<?= $livro['id'] ?></strong></td>
                                <td style="width: 60px;">
                                    <?php if (!empty($livro['capa']) && file_exists(__DIR__ . '/uploads/capas/' . $livro['capa'])): ?>
                                        <img src="uploads/capas/<?= htmlspecialchars($livro['capa']) ?>" alt="Capa" class="rounded shadow-sm" style="width: 48px; height: 64px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="rounded bg-navy text-white d-flex align-items-center justify-content-center shadow-sm" style="width: 48px; height: 64px; font-size: 1.2rem;">
                                            <i class="bi bi-book"></i>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size: 1rem;"><?= htmlspecialchars($livro['titulo']) ?></div>
                                    <small class="text-muted"><i class="bi bi-person me-1"></i><?= htmlspecialchars($livro['autor']) ?></small>
                                    <?php if (!empty($livro['categoria'])): ?>
                                        <br><span class="badge bg-light text-dark border mt-1" style="font-size: 0.75rem;"><?= htmlspecialchars($livro['categoria']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= htmlspecialchars($livro['isbn'] ?: 'N/A') ?></code></td>
                                <td>
                                    <div><?= htmlspecialchars($livro['editora'] ?: '-') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($livro['ano_publicacao'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-navy fs-6"><?= $livro['disponiveis_reais'] ?> / <?= $livro['total_exemplares_reais'] ?></span>
                                    <div style="font-size: 0.75rem;" class="text-muted">Disponíveis</div>
                                </td>
                                <td>
                                    <?php if ($livro['disponiveis_reais'] > 0): ?>
                                        <span class="badge badge-status badge-disponivel">Disponível</span>
                                    <?php else: ?>
                                        <span class="badge badge-status badge-indisponivel">Indisponível</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="livro_editar.php?id=<?= $livro['id'] ?>" class="btn btn-outline-primary" title="Editar Livro">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <?php if (isAdmin()): ?>
                                            <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalExcluirLivro<?= $livro['id'] ?>" title="Excluir ou Inativar (Admin)">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal Bootstrap de Exclusão/Inativação de Livro -->
                            <div class="modal fade" id="modalExcluirLivro<?= $livro['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content text-start">
                                        <form action="livro_excluir.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= $livro['id'] ?>">

                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title font-outfit"><i class="bi bi-exclamation-triangle-fill me-2"></i> Remover Livro do Acervo</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p class="fs-6 mb-2">Tem certeza de que deseja remover este livro do acervo?</p>
                                                <div class="alert alert-light border">
                                                    <strong>Título:</strong> <?= htmlspecialchars($livro['titulo']) ?><br>
                                                    <strong>Autor:</strong> <?= htmlspecialchars($livro['autor']) ?><br>
                                                    <strong>ISBN:</strong> <?= htmlspecialchars($livro['isbn'] ?: 'N/A') ?>
                                                </div>
                                                <small class="text-muted">
                                                    <em>Nota: Se este livro já tiver histórico de empréstimos, o sistema impedirá a exclusão física e oferecerá a opção de inativação para manter a integridade auditável.</em>
                                                </small>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-danger fw-bold">
                                                    <i class="bi bi-trash me-1"></i> Confirmar Exclusão
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>

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
            <li class="page-item <?= $paginaAtual <= 1 ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $paginaAtual - 1 ?>&busca=<?= urlencode($busca) ?>&status=<?= urlencode($statusFiltro) ?>">Anterior</a>
            </li>
            <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                <li class="page-item <?= $p === $paginaAtual ? 'active' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $p ?>&busca=<?= urlencode($busca) ?>&status=<?= urlencode($statusFiltro) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
            <li class="page-item <?= $paginaAtual >= $totalPaginas ? 'disabled' : '' ?>">
                <a class="page-link" href="?pagina=<?= $paginaAtual + 1 ?>&busca=<?= urlencode($busca) ?>&status=<?= urlencode($statusFiltro) ?>">Próximo</a>
            </li>
        </ul>
    </nav>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
