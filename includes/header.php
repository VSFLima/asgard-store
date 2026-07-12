<?php
/**
 * Asgard Store - Header Comum
 */

if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/functions.php';

$unread_notifications = 0;
if (is_logged_in()) {
    $unread_notifications = count_unread_notifications($_SESSION['user_id']);
}
$site_name = defined('SITE_NAME') ? SITE_NAME : 'Asgard Store';
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo defined('SITE_DESC') ? SITE_DESC : 'Marketplace de contas e creditos de jogos'; ?>">
    <title><?php echo isset($page_title) ? $page_title . ' | ' . $site_name : $site_name; ?></title>
    <meta name="csrf-token" content="<?php echo generate_csrf_token(); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;500;600;700&family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/neon.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <!-- Top Bar -->
    <div class="top-bar">
        <div class="container">
            <div class="top-bar-left">
                <span><i class="fas fa-bolt"></i> Bem-vindo ao <?php echo $site_name; ?></span>
            </div>
            <div class="top-bar-right">
                <a href="/suporte/"><i class="fas fa-headset"></i> Suporte</a>
                <a href="/pages/como-vender/"><i class="fas fa-question-circle"></i> Como Vender</a>
            </div>
        </div>
    </div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="container">
            <a href="/" class="logo">
                <span class="logo-icon"><i class="fas fa-gamepad"></i></span>
                <span class="logo-text">Asgard<span class="neon-green"> Store</span></span>
            </a>
            
            <div class="nav-links">
                <a href="/loja/" class="nav-link"><i class="fas fa-store"></i> Loja</a>
                <a href="/creditos/" class="nav-link"><i class="fas fa-coins"></i> Creditos</a>
                <a href="/pages/como-vender/" class="nav-link"><i class="fas fa-hand-holding-dollar"></i> Vender</a>
            </div>

            <div class="nav-search">
                <form action="/loja/busca.php" method="GET">
                    <input type="text" name="q" placeholder="Buscar contas, jogos..." class="search-input">
                    <button type="submit" class="search-btn"><i class="fas fa-search"></i></button>
                </form>
            </div>

            <div class="nav-user">
                <?php if (is_logged_in()): ?>
                    <a href="/painel/notificacoes.php" class="nav-icon-link">
                        <i class="fas fa-bell"></i>
                        <?php if ($unread_notifications > 0): ?>
                            <span class="notification-badge"><?php echo $unread_notifications; ?></span>
                        <?php endif; ?>
                    </a>
                    <div class="user-dropdown">
                        <button class="user-btn">
                            <i class="fas fa-user-circle"></i>
                            <span><?php echo sanitize($_SESSION['user_nome']); ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu">
                            <a href="/painel/"><i class="fas fa-tachometer-alt"></i> Painel</a>
                            <a href="/painel/perfil.php"><i class="fas fa-user"></i> Perfil</a>
                            <a href="/painel/anuncios.php"><i class="fas fa-tags"></i> Meus Anuncios</a>
                            <a href="/painel/compras.php"><i class="fas fa-shopping-cart"></i> Minhas Compras</a>
                            <?php if (is_admin()): ?>
                                <div class="dropdown-divider"></div>
                                <a href="/admin/"><i class="fas fa-cog"></i> Admin</a>
                            <?php endif; ?>
                            <div class="dropdown-divider"></div>
                            <a href="/auth/logout.php" class="logout-link"><i class="fas fa-sign-out-alt"></i> Sair</a>
                        </div>
                    </div>
                <?php else: ?>
                    <a href="/auth/login.php" class="btn btn-outline">Entrar</a>
                    <a href="/auth/cadastro.php" class="btn btn-primary">Cadastrar</a>
                <?php endif; ?>
            </div>

            <!-- Mobile Menu Toggle -->
            <button class="mobile-menu-toggle">
                <i class="fas fa-bars"></i>
            </button>
        </div>
    </nav>

    <!-- Flash Messages -->
    <?php if (isset($_SESSION['flash'])): ?>
        <div class="flash-message flash-<?php echo $_SESSION['flash']['type']; ?>">
            <div class="container">
                <span><?php echo sanitize($_SESSION['flash']['message']); ?></span>
                <button class="flash-close" onclick="this.parentElement.parentElement.remove()">&times;</button>
            </div>
        </div>
        <?php unset($_SESSION['flash']); ?>
    <?php endif; ?>

    <main class="main-content">
