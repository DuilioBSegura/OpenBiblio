<?php
/**
 * OpenBiblio - Tela de Login Profissional
 */
require_once __DIR__ . '/../config/auth.php';

// Se já estiver logado, redireciona para o Dashboard
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$csrfToken = generateCsrfToken();
$flashMessages = getFlashMessages();
$subPath = getAppSubPath();
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OpenBiblio</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background: linear-gradient(135deg, var(--ob-navy-dark) 0%, #0d1b2a 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            background-color: #ffffff;
            border-radius: 16px;
            box-shadow: 0 12px 32px rgba(0,0,0,0.25);
            overflow: hidden;
            width: 100%;
            max-width: 440px;
        }
        .login-header {
            background-color: var(--ob-navy-dark);
            color: #ffffff;
            padding: 2.5rem 2rem 2rem 2rem;
            text-align: center;
            border-bottom: 3px solid var(--ob-gold-primary);
        }
        .login-brand-icon {
            font-size: 3rem;
            color: var(--ob-gold-primary);
            margin-bottom: 0.5rem;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <i class="bi bi-book-half login-brand-icon"></i>
        <h2 class="font-outfit fw-bold m-0" style="letter-spacing: 1px;">OPENBIBLIO</h2>
        <p class="text-warning mb-0 fs-6">Biblioteca Comunitária</p>
    </div>
    
    <div class="p-4">
        <?php foreach ($flashMessages as $msg): ?>
            <div class="alert alert-<?= htmlspecialchars($msg['type']) ?> alert-dismissible fade show mb-3" role="alert">
                <?= htmlspecialchars($msg['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

        <form action="process_login.php" method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">

            <div class="mb-3">
                <label for="email" class="form-label fw-semibold">E-mail de Acesso</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-envelope text-muted"></i></span>
                    <input type="email" class="form-control" id="email" name="email" placeholder="admin@openbiblio.local" required autofocus>
                    <div class="invalid-feedback">Por favor, informe seu e-mail cadastrado.</div>
                </div>
            </div>

            <div class="mb-4">
                <label for="senha" class="form-label fw-semibold">Senha</label>
                <div class="input-group">
                    <span class="input-group-text bg-light"><i class="bi bi-lock text-muted"></i></span>
                    <input type="password" class="form-control" id="senha" name="senha" placeholder="••••••••" required>
                    <div class="invalid-feedback">Por favor, informe sua senha.</div>
                </div>
            </div>

            <button type="submit" class="btn btn-navy w-100 py-2 fs-6 fw-bold">
                <i class="bi bi-box-arrow-in-right me-2"></i> Entrar no Sistema
            </button>
        </form>

        <div class="mt-4 pt-3 border-top text-center text-muted" style="font-size: 0.85rem;">
            <i class="bi bi-shield-check text-success me-1"></i> Acesso restrito a operadores e bibliotecários autorizados.
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js" integrity="sha384-C6RzsynM9kWDrMNeT87bh95OGNyZPhcTNXj1NW7RuBCsyN/o0jlpcV8Qyq46cDfL" crossorigin="anonymous"></script>
</body>
</html>
