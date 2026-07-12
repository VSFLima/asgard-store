<?php
/**
 * Asgard Store - API Favoritos (AJAX)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/functions.php';

if (!is_logged_in()) {
    json_error('Faça login para usar favoritos.', 401);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    require_csrf();
    json_error('Método não permitido.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$anuncio_id = intval($input['anuncio_id'] ?? 0);
$action = $input['action'] ?? 'add';

if ($anuncio_id <= 0) {
    json_error('Anúncio inválido.');
}

// Verificar se anúncio existe
$anuncio = db_fetch("SELECT id FROM anuncios WHERE id = ? AND status = 'aprovado'", [$anuncio_id]);
if (!$anuncio) {
    json_error('Anúncio não encontrado.');
}

$user_id = $_SESSION['user_id'];

if ($action === 'remove') {
    db_delete('favoritos', 'usuario_id = ? AND anuncio_id = ?', [$user_id, $anuncio_id]);
    json_success(['action' => 'removed'], 'Removido dos favoritos.');
} else {
    // Verificar se já existe
    $exists = db_fetch("SELECT id FROM favoritos WHERE usuario_id = ? AND anuncio_id = ?", [$user_id, $anuncio_id]);
    if ($exists) {
        json_success(['action' => 'exists'], 'Já está nos favoritos.');
    } else {
        db_insert('favoritos', [
            'usuario_id' => $user_id,
            'anuncio_id' => $anuncio_id
        ]);
        json_success(['action' => 'added'], 'Adicionado aos favoritos.');
    }
}
