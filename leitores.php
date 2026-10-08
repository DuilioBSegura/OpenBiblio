<?php
/**
 * OpenBiblio - Gestão e Listagem de Leitores (Membros)
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Leitores / Membros - OpenBiblio';
$pageHeaderTitle = 'Gestão de Leitores / Tomadores';

$db = getDBConnection();

$busca = trim($_GET['busca'] ?? '');
$paginaAtual = max(1, (int)($_GET['pagina'] ?? 1));
$itensPorPagina = 10;
$offset = ($paginaAtual - 1) * $itensPorPagina;

$where = ["l.ativo = 1"];
$params = [];

if (!empty($busca)) {
    $where[] = "(l.nome_completo LIKE ? OR l.cpf LIKE ? OR l.email LIKE ? OR l.telefone LIKE ?)";
    $term = "%{$busca}%";
    $params = [$term, $term, $term, $term];
}

$whereSql = implode(" AND ", $where);

// Total de leitores para paginação
$stmtTotal = $db->prepare("SELECT COUNT(*) FROM leitores l WHERE {$whereSql}");
$stmtTotal->execute($params);
$totalRegistros = (int)$stmtTotal->fetchColumn();
$totalPaginas = max(1, ceil($totalRegistros / $itensPorPagina));

// Consulta com subquery de empréstimos ativos
$query = "
    SELECT l.*, 
           (SELECT COUNT(*) FROM emprestimos e WHERE e.leitor_id = l.id AND e.status = 'Ativo') AS emprestimos_ativos
    FROM leitores l
    WHERE {$whereSql}
    ORDER BY l.id DESC
    LIMIT {$itensPorPagina} OFFSET {$offset}
";
$stmtLeitores = $db->prepare($query);
$stmtLeitores->execute($params);
$leitores = $stmtLeitores->fetchAll();

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
    <div>
        <h2 class="font-outfit fw-bold m-0 text-navy">Cadastro de Leitores da Comunidade</h2>
        <p class="text-muted m-0">Gerencie os tomadores cadastrados e seus respetivos limites de empréstimo.</p>
    </div>
    <a href="leitor_cadastrar.php" class="btn btn-gold">
        <i class="bi bi-person-plus me-1"></i> + Novo Leitor
    </a>
</div>

<!-- Filtros de Pesquisa -->
<div class="card-ob mb-4">
    <div class="card-ob-body py-3">
        <form method="GET" action="leitores.php" class="row g-3 align-items-center">
            <div class="col-md-9 col-lg-10">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="bi bi-search text-muted"></i></span>
                    <input type="text" name="busca" class="form-control" placeholder="Buscar por Nome, CPF, E-mail ou Telefone..." value="<?= htmlspecialchars($busca) ?>">
                </div>
            </div>
            <div class="col-md-3 col-lg-2">
                <button type="submit" class="btn btn-navy w-100"><i class="bi bi-filter me-1"></i> Buscar</button>
            </div>
        </form>
    </div>
</div>

<!-- Tabela de Leitores -->
<div class="card-ob">
    <div class="card-ob-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-ob mb-0 align-middle">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nome Completo</th>
                        <th>CPF</th>
                        <th>Contato</th>
                        <th>Empréstimos Ativos</th>
                        <th>Status</th>
                        <th class="text-end">Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leitores)): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">Nenhum leitor encontrado.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leitores as $leitor): 
                            $ativos = (int)$leitor['emprestimos_ativos'];
                        ?>
                            <tr>
                                <td><strong>#<?= $leitor['id'] ?></strong></td>
                                <td>
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($leitor['nome_completo']) ?></div>
                                    <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= htmlspecialchars($leitor['endereco'] ?: 'Sem endereço') ?></small>
                                </td>
                                <td><code><?= htmlspecialchars($leitor['cpf'] ?: 'Não informado') ?></code></td>
                                <td>
                                    <div><i class="bi bi-telephone text-muted me-1"></i><?= htmlspecialchars($leitor['telefone'] ?: '-') ?></div>
                                    <small class="text-muted"><i class="bi bi-envelope me-1"></i><?= htmlspecialchars($leitor['email'] ?: '-') ?></small>
                                </td>
                                <td>
                                    <?php if ($ativos >= 3): ?>
                                        <span class="badge bg-warning text-dark fs-6" title="Limite máximo de 3 empréstimos atingido">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i> Limite Atingido (3/3)
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-navy fs-6"><?= $ativos ?> / 3 Ativos</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ((int)$leitor['ativo'] === 1): ?>
                                        <span class="badge badge-status badge-ativo">Ativo</span>
                                    <?php else: ?>
                                        <span class="badge badge-status badge-cancelado">Inativo</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <div class="btn-group btn-group-sm">
                                        <a href="leitor_editar.php?id=<?= $leitor['id'] ?>" class="btn btn-outline-primary" title="Editar Leitor">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" data-bs-target="#modalExcluirLeitor<?= $leitor['id'] ?>" title="Excluir/Inativar">
                                            <i class="bi bi-person-x"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>

                            <!-- Modal Inativação/Exclusão Leitor -->
                            <div class="modal fade" id="modalExcluirLeitor<?= $leitor['id'] ?>" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered">
                                    <div class="modal-content text-start">
                                        <form action="leitor_excluir.php" method="POST">
                                            <input type="hidden" name="csrf_token" value="<?= generateCsrfToken() ?>">
                                            <input type="hidden" name="id" value="<?= $leitor['id'] ?>">
                                            <div class="modal-header bg-danger text-white">
                                                <h5 class="modal-title font-outfit"><i class="bi bi-person-x-fill me-2"></i> Inativar/Remover Leitor</h5>
                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>Deseja remover ou inativar o leitor <strong><?= htmlspecialchars($leitor['nome_completo']) ?></strong>?</p>
                                                <small class="text-muted">Se o leitor possuir histórico de empréstimos, ele será inativado para manter o histórico da biblioteca.</small>
                                            </div>
                                            <div class="modal-footer bg-light">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                <button type="submit" class="btn btn-danger fw-bold">Confirmar</button>
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
            <?php for ($p = 1; $p <= $totalPaginas; $p++): ?>
                <li class="page-item <?= $p === $paginaAtual ? 'active' : '' ?>">
                    <a class="page-link" href="?pagina=<?= $p ?>&busca=<?= urlencode($busca) ?>"><?= $p ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
