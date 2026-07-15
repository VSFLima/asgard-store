<?php
/**
 * Asgard Store - Webhook: Confirmacao de Pagamento
 * POST: Receber confirmacao de pagamento externo
 */

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

// Verificar metodo
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo nao permitido', 405);
}

// Verificar assinatura (simplificado - em producao usar HMAC)
$secret = $_SERVER['HTTP_X_WEBHOOK_SECRET'] ?? '';
if (!hash_equals(WEBHOOK_SECRET, $secret)) {
    json_error('Assinatura invalida', 403);
}

// Ler payload
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    json_error('Payload invalido');
}

$compra_id = intval($input['compra_id'] ?? 0);
$metodo = $input['metodo'] ?? 'pix';
$comprovante = $input['comprovante'] ?? '';
$status = $input['status'] ?? 'confirmado';

if ($compra_id <= 0) {
    json_error('compra_id obrigatorio');
}

// Buscar compra
$compra = db_fetch("SELECT * FROM compras WHERE id = ? AND status = 'aguardando_pagamento'", [$compra_id]);
if (!$compra) {
    json_error('Compra nao encontrada ou ja processada');
}

// Confirmar pagamento
db_update('compras', [
    'status' => 'pagamento_confirmado',
    'metodo_pagamento' => $metodo,
    'comprovante_pix' => $comprovante,
], 'id = ?', [$compra_id]);

// Notificar vendedor
create_notification(
    $compra['vendedor_id'],
    'Pagamento Confirmado',
    "Pagamento da compra #{$compra_id} confirmado. Prepare a entrega!",
    'venda',
    '/painel/vendas.php'
);

// Log (best-effort - admin_log exige um admin_id valido, entao nao deixamos
// uma falha aqui derrubar a resposta de sucesso do webhook)
try {
    db_insert('admin_log', [
        'admin_id' => $compra['vendedor_id'],
        'acao' => 'webhook_pagamento',
        'descricao' => "Pagamento confirmado via webhook - Compra #{$compra_id}",
        'tipo' => 'financeiro',
    ]);
} catch (Throwable $e) {
    // Nao interrompe o fluxo do webhook por causa do log
}

json_success(['compra_id' => $compra_id, 'status' => 'pagamento_confirmado'], 'Pagamento confirmado');
