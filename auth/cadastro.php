<?php
/**
 * Asgard Store - Cadastro
 */

$page_title = 'Criar Conta';
require_once __DIR__ . '/../includes/functions.php';

// Se ja esta logado, redirecionar
if (is_logged_in()) {
    header('Location: /painel/');
    exit;
}

// Processar formulario
$error = '';
$success = '';
$form_data = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $nome = sanitize($_POST['nome'] ?? '');
    $sobrenome = sanitize($_POST['sobrenome'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $telefone = sanitize($_POST['telefone'] ?? '');
    $telegram = sanitize($_POST['telegram'] ?? '');
    $senha = $_POST['senha'] ?? '';
    $senha_confirm = $_POST['senha_confirm'] ?? '';
    $termos = isset($_POST['termos']);
    
    $form_data = compact('nome', 'sobrenome', 'email', 'telefone', 'telegram');
    
    // Validacoes
    if (empty($nome) || empty($sobrenome) || empty($email) || empty($senha)) {
        $error = 'Preencha todos os campos obrigatorios.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalido.';
    } elseif (strlen($senha) < 6) {
        $error = 'A senha deve ter no minimo 6 caracteres.';
    } elseif ($senha !== $senha_confirm) {
        $error = 'As senhas nao conferem.';
    } elseif (!$termos) {
        $error = 'Voce deve aceitar os termos de uso.';
    } else {
        // Verificar se email ja existe
        $exists = db_fetch(
            "SELECT id FROM usuarios WHERE email = ?",
            [$email]
        );
        
        if ($exists) {
            $error = 'Este email ja esta cadastrado.';
        } else {
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
                // Login automatico
                login_user($email, $senha);
                $_SESSION['flash'] = [
                    'type' => 'success',
                    'message' => 'Conta criada com sucesso! Bem-vindo(a)!'
                ];
                header('Location: /painel/');
                exit;
            } else {
                $error = 'Erro ao criar conta. Tente novamente.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container auth-wide">
    <div class="auth-logo">
        <a href="/">
            <span class="logo-icon"><i class="fas fa-gamepad"></i></span>
            <span class="logo-text">Asgard<span class="neon-green"> Store</span></span>
        </a>
    </div>
    
    <h1 class="auth-title">Criar Conta Gratis</h1>
    <p class="auth-subtitle">Preencha seus dados para comecar a comprar e vender</p>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>
    
    <form method="POST" action="/auth/cadastro.php">
        <?php echo csrf_field(); ?>
        
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Nome *</label>
                <input type="text" name="nome" class="form-input" 
                       placeholder="Seu nome" required
                       value="<?php echo sanitize($form_data['nome'] ?? ''); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Sobrenome *</label>
                <input type="text" name="sobrenome" class="form-input" 
                       placeholder="Seu sobrenome" required
                       value="<?php echo sanitize($form_data['sobrenome'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-group">
            <label class="form-label">Email *</label>
            <div class="input-icon">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" class="form-input" 
                       placeholder="seu@email.com" required
                       value="<?php echo sanitize($form_data['email'] ?? ''); ?>">
            </div>
        </div>
        
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Telefone (opcional)</label>
                <div class="input-icon">
                    <i class="fas fa-phone"></i>
                    <input type="tel" name="telefone" class="form-input" 
                           placeholder="(11) 99999-9999"
                           value="<?php echo sanitize($form_data['telefone'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Telegram (opcional)</label>
                <div class="input-icon">
                    <i class="fab fa-telegram"></i>
                    <input type="text" name="telegram" class="form-input" 
                           placeholder="@seuusuario"
                           value="<?php echo sanitize($form_data['telegram'] ?? ''); ?>">
                </div>
            </div>
        </div>
        
        <div class="form-row-2">
            <div class="form-group">
                <label class="form-label">Senha *</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="senha" class="form-input" 
                           placeholder="Minimo 6 caracteres" required minlength="6">
                    <button type="button" class="toggle-password" onclick="togglePassword(this)">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Confirmar Senha *</label>
                <div class="input-icon">
                    <i class="fas fa-lock"></i>
                    <input type="password" name="senha_confirm" class="form-input" 
                           placeholder="Repita a senha" required minlength="6">
                </div>
            </div>
        </div>
        
        <div class="form-group">
            <label class="checkbox-label">
                <input type="checkbox" name="termos" value="1" <?php echo isset($_POST['termos']) ? 'checked' : ''; ?> required>
                <span>Concordo com os <a href="/pages/termos.php">Termos de Uso</a> e <a href="/pages/privacidade.php">Politica de Privacidade</a></span>
            </label>
        </div>
        
        <button type="submit" class="btn btn-primary btn-block">
            <i class="fas fa-user-plus"></i> Criar Conta
        </button>
    </form>
    
    <div class="auth-divider">
        <span>ou</span>
    </div>
    
    <a href="/auth/login.php" class="btn btn-outline btn-block">
        <i class="fas fa-sign-in-alt"></i> Ja tenho uma Conta
    </a>
</div>


<?php require_once __DIR__ . '/../includes/footer.php'; ?>
