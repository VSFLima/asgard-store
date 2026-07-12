<?php
/**
 * Asgard Store - API Login (AJAX)
 */

header('Content-Type: application/json');
require_once __DIR__ . '/../../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo nao permitido', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
$email = sanitize($input['email'] ?? $_POST['email'] ?? '');
$password = $input['senha'] ?? $_POST['senha'] ?? '';

if (empty($email) || empty($password)) {
    json_error('Preencha todos os campos.');
}

$user = login_user($email, $password);
if ($user) {
    json_success([
        'id' => $user['id'],
        'nome' => $user['nome'],
        'email' => $user['email'],
        'admin' => (bool)$user['admin']
    ], 'Login realizado com sucesso!');
} else {
    json_error('Email ou senha incorretos.');
}
