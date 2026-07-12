<?php
/**
 * Asgard Store - Logout
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Limpar todos os dados da sessao
$_SESSION = [];

// Deletar cookie de sessao
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir sessao
session_destroy();

// Redirecionar para home
header('Location: /');
exit;
