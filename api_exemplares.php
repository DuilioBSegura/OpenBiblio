<?php
/**
 * OpenBiblio - API Endpoint para Busca de Exemplares Disponíveis de um Livro
 */
header('Content-Type: application/json');
require_once __DIR__ . '/includes/auth_check.php';

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
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    exit;
}
