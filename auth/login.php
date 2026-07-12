<?php
/**
 * Asgard Store - Login
 */

$page_title = 'Entrar';
require_once __DIR__ . '/../includes/functions.php';

// Se ja esta logado, redirecionar
if (is_logged_in()) {
    header('Location: /painel/');
    exit;
}

// Processar formulario
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['senha'] ?? '';
    
    if (empty($email) || empty($password)) {
        $error = 'Preencha todos os campos.';
    } else {
        $user = login_user($email, $password);
        if ($user) {
            $_SESSION['flash'] = [
                'type' => 'success',
                'message' => 'Bem-vindo(a), ' . sanitize($user['nome']) . '!'
            ];
            header('Location: /painel/');
            exit;
        } else {
            $error = 'Email ou senha incorretos.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-logo">
        <a href="/">
            <span class="logo-icon"><i class="fas fa-gamepad"></i></span>
            <span class="logo-text">Asgard<span class="neon-green"> Store</span></span>
        </a>
    </div>
    
    <h1 class="auth-title">Entrar na sua Conta</h1>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="/auth/login.php">
        <?php echo csrf_field(); ?>
        
        <div class="form-group">
            <label class="form-label">Email</label>
            <div class="input-icon">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" class="form-input" 
                       placeholder="seu@email.com" required
                       value="<?php echo sanitize($email ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Senha</label>
            <div class="input-icon">
                <i class="fas fa-lock"></i>
                <input type="password" name="senha" class="form-input" 
                       placeholder="Sua senha" required>
                <button type="button" class="toggle-password" onclick="togglePassword(this)">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
        </div>
        
        <div class="form-row">
            <label class="checkbox-label">
                <input type="checkbox" name="lembrar" value="1">
                <span>Lembrar de mim</span>
            </label>
            <a href="/auth/esqueci-senha.php" class="form-link">Esqueci a senha</a>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-sign-in-alt"></i> Entrar
        </button>
    </form>
    
    <div class="auth-divider">
        <span>ou</span>
    </div>
    
    <a href="/auth/cadastro.php" class="btn btn-outline btn-block">
        <i class="fas fa-user-plus"></i> Criar Conta Gratis
    </a>
    
    <p class="auth-footer">
        Ao entrar, voce concorda com nossos 
        <a href="/pages/termos/">Termos de Uso</a> e 
        <a href="/pages/privacidade/">Politica de Privacidade</a>.
    </p>
</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
