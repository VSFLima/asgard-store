<?php
/**
 * Asgard Store - API Register (AJAX)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo nao permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$nome = sanitize($input['nome'] ?? '');
$sobrenome = sanitize($input['sobrenome'] ?? '');
$email = sanitize($input['email'] ?? '');
$telefone = sanitize($input['telefone'] ?? '');
$telegram = sanitize($input['telegram'] ?? '');
$senha = $input['senha'] ?? '';

// Validacoes
if (empty($nome) || empty($sobrenome) || empty($email) || empty($senha)) {
    json_error('Preencha todos os campos obrigatorios.');
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_error('Email invalido.');
}

if (strlen($senha) < 6) {
    json_error('A senha deve ter no minimo 6 caracteres.');
}

// Verificar se email ja existe
$exists = db_fetch("SELECT id FROM usuarios WHERE email = ?", [$email]);
if ($exists) {
    json_error('Este email ja esta cadastrado.');
}

// Criar usuario
$user_id = db_insert('usuarios', [
    'nome' => $nome,
    'sobrenome' => $sobrenome,
    'email' => $email,
    'senha' => hash_password($senha),
    'telefone' => $telefone,
    'telegram' => $telegram,
    'status' => 'ativo'
]);

if ($user_id) {
    login_user($email, $senha);
    json_success([
        'id' => $user_id,
        'nome' => $nome,
        'email' => $email
    ], 'Conta criada com sucesso!');
} else {
    json_error('Erro ao criar conta.');
}
