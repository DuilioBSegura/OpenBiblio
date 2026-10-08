<?php
/**
 * OpenBiblio - Cabeçalho Principal (Header)
 */
require_once __DIR__ . '/../config/auth.php';
$user = getLoggedUser();
$currentPage = basename($_SERVER['PHP_SELF']);
$pageTitle = $pageTitle ?? 'OpenBiblio - Sistema de Biblioteca';
$subPath = getAppSubPath();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <!-- Chart.js para Gráficos -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Estilos Customizados OpenBiblio -->
    <link rel="stylesheet" href="<?= $subPath ?>/css/style.css">
</head>
<body>
<div id="wrapper">
    <!-- Menu Lateral (Sidebar) -->
    <?php include __DIR__ . '/sidebar.php'; ?>

    <!-- Conteúdo Principal -->
    <div id="content">
        <!-- Top Navbar -->
        <header class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button type="button" id="sidebarToggle" class="btn btn-outline-light d-lg-none">
                    <i class="bi bi-list fs-5"></i>
                </button>
                <h1 class="page-title-hdr me-auto"><?= htmlspecialchars($pageHeaderTitle ?? 'OpenBiblio') ?></h1>
            </div>
            
            <div class="user-profile-menu">
                <div class="d-none d-md-block text-end me-2">
                    <div class="fw-bold text-light" style="font-size: 0.9rem;">Olá, <?= htmlspecialchars($user['nome'] ?? 'Visitante') ?></div>
                    <small class="text-warning text-capitalize" style="font-size: 0.75rem;"><?= htmlspecialchars($user['perfil'] ?? 'Operador') ?></small>
                </div>
                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                        <div class="avatar-circle">
                            <?= strtoupper(substr($user['nome'] ?? 'A', 0, 1)) ?>
                        </div>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0" aria-labelledby="dropdownUser1">
                        <li><a class="dropdown-item" href="<?= $subPath ?>/perfil.php"><i class="bi bi-person me-2"></i> Meu Perfil</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?= $subPath ?>/auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Sair do Sistema</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Área Central da Página -->
        <main class="main-content">
            <?php include __DIR__ . '/alerts.php'; ?>
