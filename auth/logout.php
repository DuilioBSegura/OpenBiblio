<?php
/**
 * OpenBiblio - Encerramento de Sessão (Logout)
 */
require_once __DIR__ . '/../config/auth.php';

if (isLoggedIn()) {
    auditLog('LOGOUT', 'Usuário encerrou a sessão.');
}

$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
setFlashMessage('info', 'Você saiu do sistema OpenBiblio com segurança.');
header('Location: login.php');
exit;
