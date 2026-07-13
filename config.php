<?php
/**
 * Asgard Store - Configuracoes Gerais
 */

// Modo debug
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);

if (DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Seguranca de sessao
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Lax');
// Descomentar quando HTTPS estiver ativo:
// ini_set('session.cookie_secure', 1);

// Diretorios
if (!defined('ROOT_PATH')) define('ROOT_PATH', __DIR__ . '/');
if (!defined('ASSETS_PATH')) define('ASSETS_PATH', ROOT_PATH . 'assets/');
if (!defined('UPLOADS_PATH')) define('UPLOADS_PATH', ASSETS_PATH . 'img/uploads/');
if (!defined('UPLOADS_ANUNCIOS')) define('UPLOADS_ANUNCIOS', UPLOADS_PATH . 'anuncios/');
if (!defined('UPLOADS_AVATARES')) define('UPLOADS_AVATARES', UPLOADS_PATH . 'avatares/');

// URLs
if (!defined('SITE_URL')) define('SITE_URL', 'https://Asgard-Store.gamer.free');
if (!defined('SITE_NAME')) define('SITE_NAME', 'Asgard Store');
if (!defined('SITE_DESC')) define('SITE_DESC', 'Asgard Store - Marketplace de contas e creditos de jogos');

// Banco de dados
if (!defined('DB_HOST')) define('DB_HOST', 'localhost');
if (!defined('DB_NAME')) define('DB_NAME', 'asgard_store');
if (!defined('DB_USER')) define('DB_USER', 'root');
if (!defined('DB_PASS')) define('DB_PASS', '');

// Seguranca
if (!defined('CSRF_TOKEN_NAME')) define('CSRF_TOKEN_NAME', 'csrf_token');
if (!defined('SESSION_LIFETIME')) define('SESSION_LIFETIME', 3600 * 24 * 7);

// Upload
if (!defined('MAX_FILE_SIZE')) define('MAX_FILE_SIZE', 5 * 1024 * 1024);
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['image/jpeg', 'image/png', 'image/gif', 'image/webp']);
if (!defined('MAX_SCREENSHOTS')) define('MAX_SCREENSHOTS', 5);

// Comissao
if (!defined('COMISSAO_PADRAO')) define('COMISSAO_PADRAO', 10);
if (!defined('MINIMO_SAQUE')) define('MINIMO_SAQUE', 30);
if (!defined('GARANTIA_HORAS')) define('GARANTIA_HORAS', 24);

// Iniciar sessao
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
