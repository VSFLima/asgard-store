<?php
/**
 * Asgard Store - Perfil do Usuario
 */

$page_title = 'Meu Perfil';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = db_fetch("SELECT * FROM usuarios WHERE id = ?", [$user_id]);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    
    $data = [
        'nome' => sanitize($_POST['nome'] ?? ''),
        'sobrenome' => sanitize($_POST['sobrenome'] ?? ''),
        'telefone' => sanitize($_POST['telefone'] ?? ''),
        'telegram' => sanitize($_POST['telegram'] ?? ''),
        'chave_pix' => sanitize($_POST['chave_pix'] ?? ''),
        'tipo_pix' => $_POST['tipo_pix'] ?? 'aleatoria',
        // Redes sociais do usuario
        'telegram_link' => sanitize($_POST['telegram_link'] ?? ''),
        'whatsapp_link' => sanitize($_POST['whatsapp_link'] ?? ''),
        'tiktok_link' => sanitize($_POST['tiktok_link'] ?? ''),
        'instagram_link' => sanitize($_POST['instagram_link'] ?? ''),
        'youtube_link' => sanitize($_POST['youtube_link'] ?? ''),
        'discord_link' => sanitize($_POST['discord_link'] ?? ''),
    ];
    
    if (empty($data['nome']) || empty($data['sobrenome'])) {
        $error = 'Nome e sobrenome sao obrigatorios.';
    } else {
        db_update('usuarios', $data, 'id = ?', [$user_id]);
        $user = db_fetch("SELECT * FROM usuarios WHERE id = ?", [$user_id]);
        $success = 'Perfil atualizado com sucesso!';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px; max-width: 800px;">
    <h1 class="section-title" style="margin-bottom: 30px;">Meu Perfil</h1>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <form method="POST" action="/painel/perfil.php">
        <?php echo csrf_field(); ?>
        
        <!-- Dados Pessoais -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-user" style="color: var(--neon-green);"></i> Dados Pessoais</h3>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" class="form-input" required value="<?php echo sanitize($user['nome']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Sobrenome *</label>
                    <input type="text" name="sobrenome" class="form-input" required value="<?php echo sanitize($user['sobrenome']); ?>">
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Telefone</label>
                    <input type="tel" name="telefone" class="form-input" value="<?php echo sanitize($user['telefone'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Telegram</label>
                    <input type="text" name="telegram" class="form-input" placeholder="@seuusuario" value="<?php echo sanitize($user['telegram'] ?? ''); ?>">
                </div>
            </div>
        </div>
        
        <!-- PIX -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-qrcode" style="color: var(--neon-green);"></i> Dados de Pagamento (PIX)</h3>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Tipo de Chave PIX</label>
                    <select name="tipo_pix" class="form-select">
                        <option value="cpf" <?php echo ($user['tipo_pix'] ?? '') === 'cpf' ? 'selected' : ''; ?>>CPF</option>
                        <option value="cnpj" <?php echo ($user['tipo_pix'] ?? '') === 'cnpj' ? 'selected' : ''; ?>>CNPJ</option>
                        <option value="email" <?php echo ($user['tipo_pix'] ?? '') === 'email' ? 'selected' : ''; ?>>Email</option>
                        <option value="telefone" <?php echo ($user['tipo_pix'] ?? '') === 'telefone' ? 'selected' : ''; ?>>Telefone</option>
                        <option value="aleatoria" <?php echo ($user['tipo_pix'] ?? 'aleatoria') === 'aleatoria' ? 'selected' : ''; ?>>Chave Aleatoria</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Chave PIX</label>
                    <input type="text" name="chave_pix" class="form-input" placeholder="Sua chave PIX" value="<?php echo sanitize($user['chave_pix'] ?? ''); ?>">
                </div>
            </div>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 10px;">
                <i class="fas fa-info-circle"></i> Use este dados para receber pagamentos pelas suas vendas.
            </p>
        </div>
        
        <!-- Redes Sociais -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-share-alt" style="color: var(--neon-green);"></i> Minhas Redes Sociais</h3>
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 20px;">
                Link suas redes sociais para que os compradores possam te encontrar.
            </p>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-telegram" style="color: #0088CC;"></i> Telegram</label>
                    <input type="url" name="telegram_link" class="form-input" placeholder="https://t.me/seuusuario" value="<?php echo sanitize($user['telegram_link'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-whatsapp" style="color: #25D366;"></i> WhatsApp</label>
                    <input type="url" name="whatsapp_link" class="form-input" placeholder="https://wa.me/5511999999999" value="<?php echo sanitize($user['whatsapp_link'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-tiktok"></i> TikTok</label>
                    <input type="url" name="tiktok_link" class="form-input" placeholder="https://tiktok.com/@seuusuario" value="<?php echo sanitize($user['tiktok_link'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-instagram" style="color: #E4405F;"></i> Instagram</label>
                    <input type="url" name="instagram_link" class="form-input" placeholder="https://instagram.com/seuusuario" value="<?php echo sanitize($user['instagram_link'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-youtube" style="color: #FF0000;"></i> YouTube</label>
                    <input type="url" name="youtube_link" class="form-input" placeholder="https://youtube.com/@seuusuario" value="<?php echo sanitize($user['youtube_link'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-discord" style="color: #5865F2;"></i> Discord</label>
                    <input type="url" name="discord_link" class="form-input" placeholder="https://discord.gg/seuinvite" value="<?php echo sanitize($user['discord_link'] ?? ''); ?>">
                </div>
            </div>
        </div>
        
        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Salvar Alteracoes
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
