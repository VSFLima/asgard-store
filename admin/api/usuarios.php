<?php
/**
 * Asgard Store - API Admin: Acoes rapidas em usuarios
 * POST: toggle_admin, suspender, banir, resetar_senha
 */

require_once __DIR__ . '/../../includes/functions.php';
require_admin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo nao permitido', 405);
}

require_csrf();

$acao = $_POST['acao'] ?? '';
$user_id = intval($_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    json_error('Usuario invalido');
}

// Nao pode alterar a si mesmo
if ($user_id == $_SESSION['user_id']) {
    json_error('Voce nao pode alterar sua propria conta por esta API');
}

$user = db_fetch("SELECT * FROM usuarios WHERE id = ?", [$user_id]);
if (!$user) {
    json_error('Usuario nao encontrado');
}

$admin_id = $_SESSION['user_id'];

switch ($acao) {
    case 'toggle_admin':
        $novo_admin = $user['admin'] ? 0 : 1;
        db_update('usuarios', ['admin' => $novo_admin], 'id = ?', [$user_id]);
        db_insert('admin_log', [
            'admin_id' => $admin_id,
            'acao' => 'toggle_admin',
            'descricao' => "Admin " . ($novo_admin ? 'concedido' : 'removido') . " para #{$user_id}",
            'tipo' => 'usuarios',
        ]);
        json_success(['admin' => $novo_admin], 'Status de admin atualizado');
        break;

    case 'suspender':
        $novo_status = $user['status'] === 'suspenso' ? 'ativo' : 'suspenso';
        db_update('usuarios', ['status' => $novo_status], 'id = ?', [$user_id]);
        db_insert('admin_log', [
            'admin_id' => $admin_id,
            'acao' => 'suspender_usuario',
            'descricao' => "Usuario #{$user_id} " . ($novo_status === 'suspenso' ? 'suspenso' : 'reativado'),
            'tipo' => 'usuarios',
        ]);
        json_success(['status' => $novo_status], 'Status atualizado');
        break;

    case 'banir':
        $novo_status = $user['status'] === 'banido' ? 'ativo' : 'banido';
        db_update('usuarios', ['status' => $novo_status], 'id = ?', [$user_id]);
        db_insert('admin_log', [
            'admin_id' => $admin_id,
            'acao' => 'banir_usuario',
            'descricao' => "Usuario #{$user_id} " . ($novo_status === 'banido' ? 'banido' : 'desbanido'),
            'tipo' => 'usuarios',
        ]);
        json_success(['status' => $novo_status], 'Status atualizado');
        break;

    case 'resetar_senha':
        $nova_senha = bin2hex(random_bytes(6));
        $hash = hash_password($nova_senha);
        db_update('usuarios', ['senha' => $hash, 'senha_temporaria' => 1], 'id = ?', [$user_id]);
        db_insert('admin_log', [
            'admin_id' => $admin_id,
            'acao' => 'resetar_senha',
            'descricao' => "Senha resetada para usuario #{$user_id}",
            'tipo' => 'seguranca',
        ]);
        json_success(['nova_senha' => $nova_senha], 'Senha resetada');
        break;

    default:
        json_error('Acao invalida');
}
