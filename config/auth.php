<?php
/**
 * OpenBiblio - Autenticação, Sessões e Utilitários de Segurança
 * Sistema de Gerenciamento para Bibliotecas Comunitárias
 */

date_default_timezone_set(getenv('APP_TIMEZONE') ?: 'America/Sao_Paulo');

if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_only_cookies', '1');
    ini_set('session.use_strict_mode', '1');
    $forwardedProto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    $isSecure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443)
        || ($forwardedProto === 'https');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $isSecure,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// Timeout de inatividade da sessão (30 minutos)
$sessionTimeout = 1800;
if (PHP_SAPI !== 'cli' && isset($_SESSION['usuario_id']) && isset($_SESSION['last_activity'])) {
    if (time() - $_SESSION['last_activity'] > $sessionTimeout) {
        $_SESSION = [];
        session_destroy();
        session_start();
        session_regenerate_id(true);
        setFlashMessage('warning', 'Sua sessão expirou por inatividade. Faça login novamente.');
        header('Location: ' . getAppSubPath() . '/auth/login.php');
        exit;
    }
}
if (isset($_SESSION['usuario_id'])) {
    $_SESSION['last_activity'] = time();
}

require_once __DIR__ . '/database.php';

/**
 * Verifica se o usuário está autenticado no sistema
 * 
 * @return bool
 */
function isLoggedIn(): bool {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

/**
 * Verifica se o usuário atual tem perfil de Administrador (RBAC)
 * 
 * @return bool
 */
function isAdmin(): bool {
    return isLoggedIn() && (($_SESSION['usuario_perfil'] ?? '') === 'admin');
}

/**
 * Exige que o usuário esteja logado. Se não estiver, redireciona para a tela de login.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Acesso restrito! Por favor, faça login para acessar o sistema.');
        header('Location: ' . getAppSubPath() . '/auth/login.php');
        exit;
    }
}

/**
 * Exige perfil de administrador para a ação. Se não for, bloqueia o acesso (RBAC).
 */
function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('danger', 'Acesso negado: esta ação requer privilégios de Administrador.');
        header('Location: ' . getAppSubPath() . '/index.php');
        exit;
    }
}

/**
 * Retorna o usuário logado atualmente
 * 
 * @return array|null
 */
function getLoggedUser(): ?array {
    if (!isLoggedIn()) {
        return null;
    }
    return [
        'id'     => $_SESSION['usuario_id'] ?? null,
        'nome'   => $_SESSION['usuario_nome'] ?? 'Usuário',
        'email'  => $_SESSION['usuario_email'] ?? '',
        'perfil' => $_SESSION['usuario_perfil'] ?? 'bibliotecario'
    ];
}

/**
 * Gera um token CSRF e armazena na sessão
 * 
 * @return string
 */
function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Valida o token CSRF enviado via POST
 * 
 * @param string|null $token
 * @return bool
 */
function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Armazena uma mensagem flash de alerta para o usuário
 * 
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 */
function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message
    ];
}

/**
 * Recupera e limpa todas as mensagens flash armazenadas
 * 
 * @return array
 */
function getFlashMessages(): array {
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Função utilitária para sanitização simples de string
 * 
 * @param string $data
 * @return string
 */
function sanitize(?string $data): string {
    if ($data === null) return '';
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Retorna o IP real do cliente respeitando proxies reversos em rede privada (Caddy/Docker)
 * 
 * @return string
 */
function getClientIp(): string {
    $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    $isPrivateProxy = filter_var($remoteAddr, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false;
    if ($isPrivateProxy && !empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $forwardedIps = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
        if (!empty($forwardedIps[0]) && filter_var($forwardedIps[0], FILTER_VALIDATE_IP)) {
            return $forwardedIps[0];
        }
    }
    return $remoteAddr;
}

/**
 * Retorna a inicial de um nome em UTF-8 para exibição no avatar
 * 
 * @param string $name
 * @return string
 */
function getAvatarInitial(string $name): string {
    $trimmed = trim($name);
    if ($trimmed === '') {
        return 'A';
    }
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($trimmed, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return strtoupper(substr($trimmed, 0, 1));
}

/**
 * Registra um log de auditoria no banco de dados
 * 
 * @param string $acao
 * @param string $detalhes
 */
function auditLog(string $acao, string $detalhes = ''): void {
    try {
        $db = getDBConnection();
        $stmt = $db->prepare("INSERT INTO logs_sistema (usuario_id, acao, detalhes, ip) VALUES (?, ?, ?, ?)");
        $userId = $_SESSION['usuario_id'] ?? null;
        $ip = getClientIp();
        $stmt->execute([$userId, $acao, $detalhes, $ip]);
    } catch (Exception $e) {
        error_log("Erro ao gravar log de auditoria: " . $e->getMessage());
    }
}

/**
 * Retorna a raiz relativa da aplicação no navegador (ex: /SistemadeLivrosBiblio ou /openbiblio)
 * 
 * @return string
 */
function getAppSubPath(): string {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    // Detecta o diretório pai da pasta do script se estiver em auth, includes, etc.
    $parts = explode('/', trim($scriptName, '/'));
    if (empty($parts[0])) {
        return '';
    }
    // Se a aplicação estiver na raiz do servidor web ou subpasta
    $firstFolder = '/' . $parts[0];
    if (in_array(strtolower($parts[0]), ['sistemadelivrosbiblio', 'openbiblio'])) {
        return $firstFolder;
    }
    return '';
}
