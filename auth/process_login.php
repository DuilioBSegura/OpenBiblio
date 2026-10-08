<?php
/**
 * OpenBiblio - Processamento de Login
 */
require_once __DIR__ . '/../config/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit;
}

// Validar CSRF
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    setFlashMessage('danger', 'Requisição inválida (CSRF Token expirado ou nulo).');
    header('Location: login.php');
    exit;
}

$email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
$senha = $_POST['senha'] ?? '';

if (empty($email) || empty($senha)) {
    setFlashMessage('danger', 'Preencha todos os campos obrigatórios.');
    header('Location: login.php');
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare("SELECT id, nome, email, senha, perfil, ativo FROM usuarios WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $usuario = $stmt->fetch();

    if ($usuario && (int)$usuario['ativo'] === 1 && password_verify($senha, $usuario['senha'])) {
        // Regenerar ID de sessão para segurança contra Session Fixation
        session_regenerate_id(true);

        $_SESSION['usuario_id']     = $usuario['id'];
        $_SESSION['usuario_nome']   = $usuario['nome'];
        $_SESSION['usuario_email']  = $usuario['email'];
        $_SESSION['usuario_perfil'] = $usuario['perfil'];

        auditLog('LOGIN_SUCESSO', 'Usuário ' . $usuario['email'] . ' realizou login com sucesso.');
        setFlashMessage('success', 'Bem-vindo ao OpenBiblio, ' . htmlspecialchars($usuario['nome']) . '!');
        header('Location: ../index.php');
        exit;
    } else {
        auditLog('LOGIN_FALHA', 'Tentativa frustrada de login para o e-mail: ' . $email);
        setFlashMessage('danger', 'E-mail ou senha incorretos (ou usuário inativo).');
        header('Location: login.php');
        exit;
    }
} catch (Exception $e) {
    error_log("Erro no login: " . $e->getMessage());
    setFlashMessage('danger', 'Erro interno no servidor ao tentar autenticar.');
    header('Location: login.php');
    exit;
}
