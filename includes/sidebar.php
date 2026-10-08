<?php
/**
 * OpenBiblio - Sidebar Lateral de Navegação
 */
$currentPage = basename($_SERVER['PHP_SELF']);
$subPath = getAppSubPath();
?>
<nav id="sidebar">
    <div class="sidebar-header">
        <a href="<?= $subPath ?>/index.php" class="sidebar-brand">
            <i class="bi bi-book-half text-warning fs-3"></i>
            <span>OPENBIBLIO</span>
        </a>
    </div>

    <ul class="sidebar-menu">
        <li>
            <a href="<?= $subPath ?>/index.php" class="<?= in_array($currentPage, ['index.php']) ? 'active' : '' ?>">
                <i class="bi bi-house-door"></i>
                <span>Início / Dashboard</span>
            </a>
        </li>
        <li>
            <a href="<?= $subPath ?>/livros.php" class="<?= in_array($currentPage, ['livros.php', 'livro_cadastrar.php', 'livro_editar.php']) ? 'active' : '' ?>">
                <i class="bi bi-book"></i>
                <span>Acervo / Livros</span>
            </a>
        </li>
        <li>
            <a href="<?= $subPath ?>/leitores.php" class="<?= in_array($currentPage, ['leitores.php', 'leitor_cadastrar.php', 'leitor_editar.php']) ? 'active' : '' ?>">
                <i class="bi bi-people"></i>
                <span>Leitores / Membros</span>
            </a>
        </li>
        <li>
            <a href="<?= $subPath ?>/emprestimos.php" class="<?= in_array($currentPage, ['emprestimos.php', 'emprestimo_novo.php', 'emprestimo_editar.php']) ? 'active' : '' ?>">
                <i class="bi bi-arrow-left-right"></i>
                <span>Circulação / Empréstimos</span>
            </a>
        </li>
        <li>
            <a href="<?= $subPath ?>/devolucoes.php" class="<?= in_array($currentPage, ['devolucoes.php', 'devolucao_registrar.php']) ? 'active' : '' ?>">
                <i class="bi bi-journal-check"></i>
                <span>Histórico de Devoluções</span>
            </a>
        </li>
        <li>
            <a href="<?= $subPath ?>/relatorios.php" class="<?= in_array($currentPage, ['relatorios.php']) ? 'active' : '' ?>">
                <i class="bi bi-bar-chart-line"></i>
                <span>Relatórios e Indicadores</span>
            </a>
        </li>
    </ul>

    <div class="sidebar-footer">
        <ul class="sidebar-menu p-0">
            <li>
                <a href="<?= $subPath ?>/perfil.php" class="<?= $currentPage === 'perfil.php' ? 'active' : '' ?>">
                    <i class="bi bi-gear"></i>
                    <span>Configurações / Perfil</span>
                </a>
            </li>
            <li>
                <a href="<?= $subPath ?>/auth/logout.php" class="text-danger">
                    <i class="bi bi-box-arrow-right text-danger"></i>
                    <span>Sair</span>
                </a>
            </li>
        </ul>
    </div>
</nav>
