<?php
/**
 * OpenBiblio - Autenticação, Sessões e Utilitários de Segurança
 * Sistema de Gerenciamento para Bibliotecas Comunitárias
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
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
 * Exige que o usuário esteja logado. Se não estiver, redireciona para a tela de login.
 */
function requireLogin(): void {
    if (!isLoggedIn()) {
        setFlashMessage('warning', 'Acesso restrito! Por favor, faça login para acessar o sistema.');
        // Determina a URL relativa para o login
        header('Location: ' . getAppSubPath() . '/auth/login.php');
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
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
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
