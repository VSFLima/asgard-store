<?php
/**
 * Asgard Store - API Painel: Notificacoes
 * POST: marcar_lida, marcar_todas, contar_nao_lidas
 */

require_once __DIR__ . '/../../includes/functions.php';
require_login();

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $acao = $_POST['acao'] ?? '';

    switch ($acao) {
        case 'marcar_lida':
            $id = intval($_POST['id'] ?? 0);
            if ($id > 0) {
                db_update('notificacoes', ['lida' => 1], 'id = ? AND usuario_id = ?', [$id, $user_id]);
            }
            json_success([], 'Notificacao marcada como lida');
            break;

        case 'marcar_todas':
            db_update('notificacoes', ['lida' => 1], 'usuario_id = ? AND lida = 0', [$user_id]);
            json_success([], 'Todas marcadas como lidas');
            break;

        default:
            json_error('Acao invalida');
    }
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $nao_lidas = db_count('notificacoes', 'usuario_id = ? AND lida = 0', [$user_id]);
    $total = db_count('notificacoes', 'usuario_id = ?', [$user_id]);
    json_success(['nao_lidas' => $nao_lidas, 'total' => $total]);
} else {
    json_error('Metodo nao permitido', 405);
}
