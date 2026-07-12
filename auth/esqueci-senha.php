<?php
/**
 * Asgard Store - Esqueci a Senha
 */

$page_title = 'Esqueci a Senha';
require_once __DIR__ . '/../includes/functions.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $email = sanitize($_POST['email'] ?? '');
    
    if (empty($email)) {
        $error = 'Informe seu email.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalido.';
    } else {
        $user = db_fetch(
            "SELECT id, nome FROM usuarios WHERE email = ? AND status = 'ativo'",
            [$email]
        );
        
        if ($user) {
            // Gerar token de recuperacao
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            db_update('usuarios', [
                'token_recuperacao' => $token,
                'token_expira' => $expires
            ], 'id = ?', [$user['id']]);
            
            // Em producao, enviaria email com o link
            // Por agora, mostrar mensagem de sucesso
            $success = 'Se o email ' . $email . ' estiver cadastrado, voce recebera um link de recuperacao.';
        } else {
            // Mensagem generica por seguranca
            $success = 'Se o email ' . $email . ' estiver cadastrado, voce recebera um link de recuperacao.';
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
    
    <h1 class="auth-title">Esqueci a Senha</h1>
    <p class="auth-subtitle">Informe seu email para receber um link de recuperacao</p>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <?php if (!empty($success)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="/auth/esqueci-senha.php">
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
        
        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-paper-plane"></i> Enviar Link de Recuperacao
        </button>
    </form>
    
    <div class="auth-links">
        <a href="/auth/login.php"><i class="fas fa-arrow-left"></i> Voltar ao Login</a>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
