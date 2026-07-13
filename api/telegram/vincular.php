<?php
/**
 * Asgard Store - Vincular Telegram
 * Gera token unico para vincular conta Telegram
 */

$page_title = 'Vincular Telegram';
require_once __DIR__ . '/../../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = db_fetch("SELECT * FROM usuarios WHERE id = ?", [$user_id]);

// Gerar token se nao existe
if (empty($user['telegram_link'])) {
    $token = bin2hex(random_bytes(16));
    db_update('usuarios', ['telegram_link' => $token], 'id = ?', [$user_id]);
    $user['telegram_link'] = $token;
}

$telegram_configured = !empty($bot_token) && $bot_token !== 'SEU_BOT_TOKEN_AQUI';
$telegram_url = $telegram_configured 
    ? "https://t.me/" . "AsgardStoreBot" . "?start=" . $user['telegram_link']
    : '#';

// Verificar se ja esta vinculado
$vinculado = db_fetch("SELECT * FROM telegram_users WHERE usuario_id = ? AND ativo = 1", [$user_id]);

require_once __DIR__ . '/../../includes/header.php';
?>

<div class="container" style="padding-top: 40px; padding-bottom: 60px; max-width: 600px;">
    <h1 class="section-title" style="text-align: center; margin-bottom: 30px;">
        <i class="fab fa-telegram" style="color: #0088CC;"></i> Vincular Telegram
    </h1>

    <?php if ($vinculado): ?>
    <div class="card" style="text-align: center; padding: 30px; border-left: 3px solid var(--neon-green); margin-bottom: 20px;">
        <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--neon-green); margin-bottom: 15px; display: block;"></i>
        <h3 style="color: var(--text); margin-bottom: 10px;">Vinculado!</h3>
        <p style="color: var(--text-muted);">Sua conta esta vinculada ao Telegram.</p>
        <?php if ($vinculado['username']): ?>
            <p style="color: #0088CC; font-weight: 600;">@<?php echo sanitize($vinculado['username']); ?></p>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="card" style="padding: 25px;">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-link" style="color: var(--neon-green);"></i> Como vincular</h3>
        <div style="color: var(--text-muted); line-height: 1.8;">
            <p><strong>1.</strong> Abra o Telegram e busque nosso bot</p>
            <p><strong>2.</strong> Envie o comando abaixo para o bot:</p>
            
            <div style="background: var(--bg-input); padding: 12px; border-radius: 8px; margin: 10px 0; font-family: monospace; word-break: break-all;">
                /start <?php echo $user['telegram_link']; ?>
            </div>

            <p><strong>3.</strong> Pronto! Voce recebera notificacoes no Telegram.</p>
        </div>
    </div>

    <div class="card" style="padding: 25px; margin-top: 20px;">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-bell" style="color: var(--neon-green);"></i> Notificacoes</h3>
        <p style="color: var(--text-muted); margin-bottom: 15px;">Escolha quais notificacoes receber:</p>

        <form method="POST">
            <?php echo csrf_field(); ?>
            
            <label style="display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border-color); cursor: pointer;">
                <input type="checkbox" name="notificar_compras" value="1" 
                       <?php echo ($vinculado['notificar_compras'] ?? 1) ? 'checked' : ''; ?>
                       style="width: 18px; height: 18px; accent-color: var(--neon-green);">
                <div>
                    <strong style="color: var(--text);">Compras</strong>
                    <div style="color: var(--text-muted); font-size: 0.85rem;">Quando alguem comprar seu anuncio</div>
                </div>
            </label>

            <label style="display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px solid var(--border-color); cursor: pointer;">
                <input type="checkbox" name="notificar_vendas" value="1"
                       <?php echo ($vinculado['notificar_vendas'] ?? 1) ? 'checked' : ''; ?>
                       style="width: 18px; height: 18px; accent-color: var(--neon-green);">
                <div>
                    <strong style="color: var(--text);">Vendas</strong>
                    <div style="color: var(--text-muted); font-size: 0.85rem;">Status de suas compras</div>
                </div>
            </label>

            <label style="display: flex; align-items: center; gap: 10px; padding: 10px 0; cursor: pointer;">
                <input type="checkbox" name="notificar_saque" value="1"
                       <?php echo ($vinculado['notificar_saque'] ?? 1) ? 'checked' : ''; ?>
                       style="width: 18px; height: 18px; accent-color: var(--neon-green);">
                <div>
                    <strong style="color: var(--text);">Saques</strong>
                    <div style="color: var(--text-muted); font-size: 0.85rem;">Quando seu saque for processado</div>
                </div>
            </label>

            <button type="submit" class="btn btn-primary" style="margin-top: 15px;">
                <i class="fas fa-save"></i> Salvar Preferencias
            </button>
        </form>
    </div>

    <div style="text-align: center; margin-top: 20px;">
        <a href="/painel/perfil.php" style="color: var(--neon-green); text-decoration: none;">
            <i class="fas fa-arrow-left"></i> Voltar ao Perfil
        </a>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>