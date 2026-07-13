<?php
/**
 * Asgard Store - API: Recuperacao de Senha
 * POST: Enviar email de recuperacao
 */

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo nao permitido', 405);
}

require_csrf();

$email = sanitize($_POST['email'] ?? '');

if (empty($email)) {
    json_error('Email e obrigatorio');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Email invalido');
}

$user = db_fetch("SELECT * FROM usuarios WHERE email = ?", [$email]);

// Sempre retornar sucesso por seguranca (nao revelar se email existe)
if ($user) {
    // Gerar token de recuperacao
    $token = bin2hex(random_bytes(32));
    $expira = date('Y-m-d H:i:s', strtotime('+1 hour'));

    db_update('usuarios', [
        'token_recuperacao' => $token,
        'token_expira' => $expira,
    ], 'id = ?', [$user['id']]);

    // Enviar email (simplificado - em producao usar PHPMailer ou similar)
    $link = SITE_URL . "/auth/nova-senha.php?token={$token}";
    $assunto = "Recuperacao de Senha - " . SITE_NAME;
    $mensagem = "Olá {$user['nome']},\n\n";
    $mensagem .= "Voce solicitou a recuperacao de senha.\n";
    $mensagem .= "Clique no link abaixo para redefinir sua senha:\n\n";
    $mensagem .= "{$link}\n\n";
    $mensagem .= "Este link expira em 1 hora.\n";
    $mensagem .= "Se voce nao solicitou, ignore este email.\n\n";
    $mensagem .= "Equipe " . SITE_NAME;

    // Tentar enviar (fallback: log)
    $headers = "From: " . SITE_NAME . " <noreply@" . parse_url(SITE_URL, PHP_URL_HOST) . ">\r\n";
    $headers .= "Reply-To: suporte@" . parse_url(SITE_URL, PHP_URL_HOST) . "\r\n";

    // Em producao, descomentar: mail($email, $assunto, $mensagem, $headers);

    // Log para debug
    error_log("[Asgard Store] Email de recuperacao para {$email}: {$link}");
}

json_success([], 'Se o email estiver cadastrado, voce recebera as instrucoes em breve.');
