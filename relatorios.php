<?php
/**
 * OpenBiblio - Relatórios e Indicadores da Biblioteca
 */
require_once __DIR__ . '/includes/auth_check.php';

$pageTitle = 'Relatórios - OpenBiblio';
$pageHeaderTitle = 'Relatórios e Indicadores Acervísticos';

$db = getDBConnection();

$tipoRelatorio = trim($_GET['tipo'] ?? 'mais_emprestados');
$dataInicio    = trim($_GET['data_inicio'] ?? date('Y-m-01'));
$dataFim       = trim($_GET['data_fim'] ?? date('Y-m-t'));

$dadosRelatorio = [];

try {
    if ($tipoRelatorio === 'mais_emprestados') {
        $stmt = $db->prepare("
            SELECT b.titulo, b.autor, b.isbn, COUNT(e.id) AS total_saidas
            FROM livros b
            JOIN emprestimos e ON b.id = e.livro_id
            WHERE DATE(e.data_emprestimo) BETWEEN ? AND ?
            GROUP BY b.id
            ORDER BY total_saidas DESC
            LIMIT 20
        ");
        $stmt->execute([$dataInicio, $dataFim]);
        $dadosRelatorio = $stmt->fetchAll();
    } elseif ($tipoRelatorio === 'leitores_ativos') {
        $stmt = $db->prepare("
            SELECT l.nome_completo, l.cpf, l.telefone, COUNT(e.id) AS total_emprestimos
            FROM leitores l
            JOIN emprestimos e ON l.id = e.leitor_id
            WHERE DATE(e.data_emprestimo) BETWEEN ? AND ?
            GROUP BY l.id
            ORDER BY total_emprestimos DESC
            LIMIT 20
        ");
        $stmt->execute([$dataInicio, $dataFim]);
        $dadosRelatorio = $stmt->fetchAll();
    } elseif ($tipoRelatorio === 'atrasados') {
        $stmt = $db->query("
            SELECT e.id, l.nome_completo AS leitor_nome, l.telefone AS leitor_telefone,
                   b.titulo AS livro_titulo, ex.codigo_exemplar,
                   e.data_emprestimo, e.data_prevista_devolucao,
                   DATEDIFF(CURRENT_DATE(), e.data_prevista_devolucao) AS dias_atraso
            FROM emprestimos e
            JOIN leitores l ON e.leitor_id = l.id
            JOIN livros b ON e.livro_id = b.id
            JOIN exemplares ex ON e.exemplar_id = ex.id
            WHERE e.status = 'Ativo' AND e.data_prevista_devolucao < CURRENT_DATE()
            ORDER BY dias_atraso DESC
        ");
        $dadosRelatorio = $stmt->fetchAll();
    } elseif ($tipoRelatorio === 'devolucoes_periodo') {
        $stmt = $db->prepare("
            SELECT d.id, d.data_devolucao_real, d.estado_exemplar,
                   l.nome_completo AS leitor_nome, b.titulo AS livro_titulo, ex.codigo_exemplar
            FROM devolucoes d
            JOIN emprestimos e ON d.emprestimo_id = e.id
            JOIN leitores l ON e.leitor_id = l.id
            JOIN livros b ON e.livro_id = b.id
            JOIN exemplares ex ON e.exemplar_id = ex.id
            WHERE DATE(d.data_devolucao_real) BETWEEN ? AND ?
            ORDER BY d.data_devolucao_real DESC
        ");
        $stmt->execute([$dataInicio, $dataFim]);
        $dadosRelatorio = $stmt->fetchAll();
    }
} catch (Exception $e) {
    error_log("Erro ao gerar relatório: " . $e->getMessage());
}

include __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3 btn-print-hide">
    <div>
        <h2 class="font-outfit fw-bold m-0 text-navy">Relatórios Gerenciais</h2>
        <p class="text-muted m-0">Gere relatórios sintéticos de utilização do acervo e circulantes.</p>
    </div>
    <button onclick="window.print()" class="btn btn-navy">
        <i class="bi bi-printer me-1"></i> Imprimir Relatório
    </button>
</div>

<!-- Card de Filtros de Relatórios -->
<div class="card-ob mb-4 btn-print-hide">
    <div class="card-ob-body py-3">
        <form method="GET" action="relatorios.php" class="row g-3 align-items-center">
            <div class="col-md-4">
                <label class="form-label fw-bold">Tipo de Relatório</label>
                <select name="tipo" class="form-select">
                    <option value="mais_emprestados" <?= $tipoRelatorio === 'mais_emprestados' ? 'selected' : '' ?>>Livros Mais Emprestados</option>
                    <option value="leitores_ativos" <?= $tipoRelatorio === 'leitores_ativos' ? 'selected' : '' ?>>Leitores Mais Ativos</option>
                    <option value="atrasados" <?= $tipoRelatorio === 'atrasados' ? 'selected' : '' ?>>Empréstimos Atrasados</option>
                    <option value="devolucoes_periodo" <?= $tipoRelatorio === 'devolucoes_periodo' ? 'selected' : '' ?>>Devoluções Registradas no Período</option>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Data Inicial</label>
                <input type="date" name="data_inicio" class="form-control" value="<?= htmlspecialchars($dataInicio) ?>">
            </div>

            <div class="col-md-3">
                <label class="form-label fw-bold">Data Final</label>
                <input type="date" name="data_fim" class="form-control" value="<?= htmlspecialchars($dataFim) ?>">
            </div>

            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-gold w-100 fw-bold"><i class="bi bi-bar-chart"></i> Gerar</button>
            </div>
        </form>
    </div>
</div>

<!-- Exibição de Dados do Relatório Gerado -->
<div class="card-ob">
    <div class="card-ob-header">
        <span>
            <i class="bi bi-file-earmark-text me-2 text-warning"></i> 
            Relatório: 
            <?php 
                echo match($tipoRelatorio) {
                    'mais_emprestados'   => 'Livros Mais Emprestados no Período',
                    'leitores_ativos'    => 'Leitores Mais Ativos no Período',
                    'atrasados'          => 'Relatório Sintético de Empréstimos Atrasados',
                    'devolucoes_periodo' => 'Histórico de Devoluções no Período',
                    default              => 'Relatório'
                };
            ?>
        </span>
        <small class="text-white opacity-75">Período: <?= date('d/m/Y', strtotime($dataInicio)) ?> até <?= date('d/m/Y', strtotime($dataFim)) ?></small>
    </div>
    <div class="card-ob-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-ob mb-0 align-middle">
                <?php if ($tipoRelatorio === 'mais_emprestados'): ?>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Título da Obra</th>
                            <th>Autor</th>
                            <th>ISBN</th>
                            <th class="text-end">Total de Saídas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dadosRelatorio)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Nenhum dado registrado para o período informado.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dadosRelatorio as $idx => $item): ?>
                                <tr>
                                    <td><strong><?= $idx + 1 ?></strong></td>
                                    <td class="fw-bold"><?= htmlspecialchars($item['titulo']) ?></td>
                                    <td><?= htmlspecialchars($item['autor']) ?></td>
                                    <td><code><?= htmlspecialchars($item['isbn'] ?: 'N/A') ?></code></td>
                                    <td class="text-end"><span class="badge bg-navy fs-6"><?= $item['total_saidas'] ?> empréstimos</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                <?php elseif ($tipoRelatorio === 'leitores_ativos'): ?>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nome do Leitor</th>
                            <th>CPF</th>
                            <th>Telefone</th>
                            <th class="text-end">Total Empréstimos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dadosRelatorio)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Nenhum dado registrado no período.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dadosRelatorio as $idx => $item): ?>
                                <tr>
                                    <td><strong><?= $idx + 1 ?></strong></td>
                                    <td class="fw-bold"><?= htmlspecialchars($item['nome_completo']) ?></td>
                                    <td><code><?= htmlspecialchars($item['cpf'] ?: '-') ?></code></td>
                                    <td><?= htmlspecialchars($item['telefone'] ?: '-') ?></td>
                                    <td class="text-end"><span class="badge bg-gold text-dark fs-6"><?= $item['total_emprestimos'] ?> retiras</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                <?php elseif ($tipoRelatorio === 'atrasados'): ?>
                    <thead>
                        <tr>
                            <th>ID Empréstimo</th>
                            <th>Leitor / Contato</th>
                            <th>Obra / Exemplar</th>
                            <th>Data Saída</th>
                            <th>Prazo Previsto</th>
                            <th>Dias em Atraso</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dadosRelatorio)): ?>
                            <tr><td colspan="6" class="text-center text-success py-4"><i class="bi bi-check-circle me-1"></i> Nenhum empréstimo atrasado no momento!</td></tr>
                        <?php else: ?>
                            <?php foreach ($dadosRelatorio as $item): ?>
                                <tr class="table-danger">
                                    <td><strong>#<?= $item['id'] ?></strong></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($item['leitor_nome']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($item['leitor_telefone'] ?: 'Sem fone') ?></small>
                                    </td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($item['livro_titulo']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($item['codigo_exemplar']) ?></small>
                                    </td>
                                    <td><?= date('d/m/Y', strtotime($item['data_emprestimo'])) ?></td>
                                    <td><strong class="text-danger"><?= date('d/m/Y', strtotime($item['data_prevista_devolucao'])) ?></strong></td>
                                    <td><span class="badge bg-danger fs-6"><?= $item['dias_atraso'] ?> dias de atraso</span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>

                <?php elseif ($tipoRelatorio === 'devolucoes_periodo'): ?>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Data/Hora Devolução</th>
                            <th>Leitor</th>
                            <th>Livro / Exemplar</th>
                            <th>Estado na Entrega</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($dadosRelatorio)): ?>
                            <tr><td colspan="5" class="text-center text-muted py-4">Nenhuma devolução no período.</td></tr>
                        <?php else: ?>
                            <?php foreach ($dadosRelatorio as $item): ?>
                                <tr>
                                    <td><strong>#<?= $item['id'] ?></strong></td>
                                    <td><?= date('d/m/Y H:i', strtotime($item['data_devolucao_real'])) ?></td>
                                    <td><?= htmlspecialchars($item['leitor_nome']) ?></td>
                                    <td><?= htmlspecialchars($item['livro_titulo']) ?> (<code><?= htmlspecialchars($item['codigo_exemplar']) ?></code>)</td>
                                    <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($item['estado_exemplar']) ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
