<?php
/**
 * Asgard Store - API Admin: Processar saques
 * POST: aprovar, rejeitar
 */

require_once __DIR__ . '/../../includes/functions.php';
require_admin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo nao permitido', 405);
}

require_csrf();

$acao = $_POST['acao'] ?? '';
$saque_id = intval($_POST['saque_id'] ?? 0);

if ($saque_id <= 0) {
    json_error('Saque invalido');
}

$saque = db_fetch("SELECT * FROM saques WHERE id = ? AND status IN ('pendente', 'processando')", [$saque_id]);
if (!$saque) {
    json_error('Saque nao encontrado ou ja processado');
}

$admin_id = $_SESSION['user_id'];

switch ($acao) {
    case 'aprovar':
        db_update('saques', ['status' => 'pago', 'processado_em' => date('Y-m-d H:i:s')], 'id = ?', [$saque_id]);
        create_notification($saque['usuario_id'], 'Saque Aprovado', "Seu saque de R$ " . number_format($saque['valor'], 2, ',', '.') . " foi processado!", 'sistema', '/painel/saldo.php');
        db_insert('admin_log', ['admin_id' => $admin_id, 'acao' => 'aprovar_saque', 'descricao' => "Saque #{$saque_id} - R$ " . number_format($saque['valor'], 2, ',', '.'), 'tipo' => 'financeiro']);
        json_success(['status' => 'pago'], 'Saque aprovado');
        break;

    case 'rejeitar':
        $motivo = sanitize($_POST['motivo'] ?? 'Rejeitado pelo admin');
        // Devolver saldo
        db_query("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?", [$saque['valor'], $saque['usuario_id']]);
        db_update('saques', ['status' => 'rejeitado', 'motivo_rejeicao' => $motivo, 'processado_em' => date('Y-m-d H:i:s')], 'id = ?', [$saque_id]);
        create_notification($saque['usuario_id'], 'Saque Rejeitado', "Seu saque foi rejeitado. Motivo: {$motivo}", 'sistema', '/painel/saldo.php');
        db_insert('admin_log', ['admin_id' => $admin_id, 'acao' => 'rejeitar_saque', 'descricao' => "Saque #{$saque_id} rejeitado", 'tipo' => 'financeiro']);
        json_success(['status' => 'rejeitado'], 'Saque rejeitado e saldo devolvido');
        break;

    default:
        json_error('Acao invalida');
}
