<?php
/**
 * OpenBiblio - API Endpoint para Busca de Exemplares Disponíveis de um Livro
 */
require_once __DIR__ . '/config/auth.php';
header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Sessão expirada ou usuário não autenticado.']);
    exit;
}

$livroId = filter_input(INPUT_GET, 'livro_id', FILTER_VALIDATE_INT);

if (!$livroId) {
    echo json_encode(['status' => 'error', 'message' => 'ID do livro inválido.']);
    exit;
}

try {
    $db = getDBConnection();
    $stmt = $db->prepare("
        SELECT id, codigo_exemplar, status 
        FROM exemplares 
        WHERE livro_id = ? AND status = 'Disponível' 
        ORDER BY codigo_exemplar ASC
    ");
    $stmt->execute([$livroId]);
    $exemplares = $stmt->fetchAll();

    echo json_encode(['status' => 'success', 'exemplares' => $exemplares]);
    exit;
} catch (Exception $e) {
    error_log("Erro em api_exemplares: " . $e->getMessage());
    echo json_encode(['status' => 'error', 'message' => 'Erro interno ao consultar exemplares.']);
    exit;
}
