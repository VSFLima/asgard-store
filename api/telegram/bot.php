<?php
/**
 * Asgard Store - Bot Telegram: Webhook
 * POST: Receber atualizacoes do Telegram
 */

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['message'])) {
    exit;
}

$message = $input['message'];
$chat_id = $message['chat']['id'] ?? 0;
$text = $message['text'] ?? '';
$username = $message['from']['username'] ?? '';
$user_id_telegram = $message['from']['id'] ?? 0;

if (empty($text) || $chat_id <= 0) {
    exit;
}

// Comando /start com token de vinculacao
if (strpos($text, '/start ') === 0) {
    $token = trim(substr($text, 7));
    $user = db_fetch("SELECT * FROM usuarios WHERE telegram_link = ?", [$token]);

    if ($user) {
        // Verificar ja vinculado
        $exists = db_fetch("SELECT id FROM telegram_users WHERE usuario_id = ?", [$user['id']]);
        if ($exists) {
            db_update('telegram_users', ['chat_id' => $chat_id, 'username' => $username, 'ativo' => 1], 'usuario_id = ?', [$user['id']]);
        } else {
            db_insert('telegram_users', [
                'usuario_id' => $user['id'],
                'chat_id' => $chat_id,
                'username' => $username,
                'notificar_compras' => 1,
                'notificar_vendas' => 1,
                'notificar_saque' => 1,
                'ativo' => 1,
            ]);
        }

        // Responder
        $response = [
            'chat_id' => $chat_id,
            'text' => "✅ Conta vinculada com sucesso!\n\nOlá {$user['nome']}! Voce recebera notificacoes de compras, vendas e saques.",
        ];
    } else {
        $response = [
            'chat_id' => $chat_id,
            'text' => "❌ Token invalido. Acesse seu perfil na Asgard Store para obter o token correto.",
        ];
    }

    sendTelegramMessage($response);
    exit;
}

// Comando /status
if ($text === '/status') {
    $tg_user = db_fetch("SELECT tu.*, u.nome FROM telegram_users tu JOIN usuarios u ON tu.usuario_id = u.id WHERE tu.chat_id = ? AND tu.ativo = 1", [$chat_id]);
    if ($tg_user) {
        $response = [
            'chat_id' => $chat_id,
            'text' => "👤 Conta vinculada: {$tg_user['nome']}\n\nNotificacoes:\n• Compras: " . ($tg_user['notificar_compras'] ? '✅' : '❌') . "\n• Vendas: " . ($tg_user['notificar_vendas'] ? '✅' : '❌') . "\n• Saques: " . ($tg_user['notificar_saque'] ? '✅' : '❌'),
        ];
    } else {
        $response = [
            'chat_id' => $chat_id,
            'text' => "❌ Nenhuma conta vinculada. Use /start <token> para vincular.",
        ];
    }
    sendTelegramMessage($response);
    exit;
}

// Funcao auxiliar para enviar mensagem
function sendTelegramMessage(array $data): void {
    $bot_token = 'SEU_BOT_TOKEN_AQUI'; // Configurar
    $url = "https://api.telegram.org/bot{$bot_token}/sendMessage";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_RETURNTRANSFER => true,
    ]);
    curl_exec($ch);
    curl_close($ch);
}
