<?php
/**
 * Asgard Store - Admin: Configuracoes Gerais
 */

$page_title = 'Configuracoes';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

// Configuracoes padrao do banco
$configs_default = [
    'site_nome' => ['label' => 'Nome do Site', 'tipo' => 'text', 'valor' => SITE_NAME],
    'site_desc' => ['label' => 'Descricao do Site', 'tipo' => 'textarea', 'valor' => SITE_DESC],
    'comissao_percentual' => ['label' => 'Comissao (%)', 'tipo' => 'number', 'valor' => COMISSAO_PADRAO, 'desc' => 'Percentual cobrado por venda'],
    'minimo_saque' => ['label' => 'Minimo para Saque (R$)', 'tipo' => 'number', 'valor' => MINIMO_SAQUE, 'desc' => 'Valor minimo para solicitar saque'],
    'garantia_horas' => ['label' => 'Garantia (horas)', 'tipo' => 'number', 'valor' => GARANTIA_HORAS, 'desc' => 'Horas de garantia apos entrega'],
    'max_anuncios_usuario' => ['label' => 'Max Anuncios/Usuario', 'tipo' => 'number', 'valor' => 20, 'desc' => 'Limite de anuncios por vendedor'],
    'manutencao' => ['label' => 'Modo Manutencao', 'tipo' => 'toggle', 'valor' => 0, 'desc' => 'Ativar tela de manutencao para usuarios'],
    'mensagem_manutencao' => ['label' => 'Mensagem de Manutencao', 'tipo' => 'textarea', 'valor' => 'Sistema em manutencao. Volte em breve!'],
    'whatsapp_suporte' => ['label' => 'WhatsApp Suporte', 'tipo' => 'text', 'valor' => '', 'desc' => 'Numero do WhatsApp para suporte'],
    'telegram_grupo' => ['label' => 'Link Telegram', 'tipo' => 'url', 'valor' => '', 'desc' => 'Link do grupo/canal Telegram'],
];

// Buscar configs do banco
$db_configs = db_fetch_all("SELECT chave, valor FROM configuracoes");
$configs_db = [];
foreach ($db_configs as $c) {
    $configs_db[$c['chave']] = $c['valor'];
}

// Merge: banco sobrescreve padrao
$configs = $configs_default;
foreach ($configs_db as $chave => $valor) {
    if (isset($configs[$chave])) {
        $configs[$chave]['valor'] = $valor;
    }
}

// Salvar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    foreach ($configs as $chave => $config) {
        if ($config['tipo'] === 'toggle') {
            $valor = isset($_POST[$chave]) ? 1 : 0;
        } else {
            $valor = sanitize($_POST[$chave] ?? '');
        }

        // Verificar se ja existe
        $exists = db_fetch("SELECT id FROM configuracoes WHERE chave = ?", [$chave]);
        if ($exists) {
            db_update('configuracoes', ['valor' => $valor], 'chave = ?', [$chave]);
        } else {
            db_insert('configuracoes', [
                'chave' => $chave,
                'valor' => $valor,
                'descricao' => $config['desc'] ?? $config['label'],
            ]);
        }

        // Atualizar constante em runtime para a sessao atual
        if ($chave === 'comissao_percentual') define('COMISSAO_PADRAO', floatval($valor));
        if ($chave === 'minimo_saque') define('MINIMO_SAQUE', floatval($valor));
        if ($chave === 'garantia_horas') define('GARANTIA_HORAS', intval($valor));
    }

    db_insert('admin_log', [
        'admin_id' => $_SESSION['user_id'],
        'acao' => 'atualizar_config',
        'descricao' => 'Configuracoes gerais atualizadas',
        'tipo' => 'config',
    ]);

    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Configuracoes salvas com sucesso!'];
    header('Location: ' . SITE_URL . '/admin/config.php');
    exit;
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px; max-width: 800px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-cog" style="color: var(--neon-green);"></i> Configuracoes Gerais
    </h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST">
        <?php echo csrf_field(); ?>

        <!-- Site -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-globe" style="color: var(--neon-green);"></i> Informacoes do Site</h3>
            <div class="form-group">
                <label class="form-label"><?php echo $configs['site_nome']['label']; ?></label>
                <input type="text" name="site_nome" class="form-input" value="<?php echo sanitize($configs['site_nome']['valor']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $configs['site_desc']['label']; ?></label>
                <textarea name="site_desc" class="form-input" rows="3"><?php echo sanitize($configs['site_desc']['valor']); ?></textarea>
            </div>
        </div>

        <!-- Financeiro -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-dollar-sign" style="color: var(--neon-green);"></i> Configuracoes Financeiras</h3>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label"><?php echo $configs['comissao_percentual']['label']; ?></label>
                    <input type="number" name="comissao_percentual" class="form-input" step="0.1" min="0" max="100"
                           value="<?php echo sanitize($configs['comissao_percentual']['valor']); ?>">
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;"><?php echo $configs['comissao_percentual']['desc']; ?></p>
                </div>
                <div class="form-group">
                    <label class="form-label"><?php echo $configs['minimo_saque']['label']; ?></label>
                    <input type="number" name="minimo_saque" class="form-input" step="0.01" min="0"
                           value="<?php echo sanitize($configs['minimo_saque']['valor']); ?>">
                    <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;"><?php echo $configs['minimo_saque']['desc']; ?></p>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label"><?php echo $configs['garantia_horas']['label']; ?></label>
                <input type="number" name="garantia_horas" class="form-input" min="0"
                       value="<?php echo sanitize($configs['garantia_horas']['valor']); ?>">
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;"><?php echo $configs['garantia_horas']['desc']; ?></p>
            </div>
        </div>

        <!-- Limites -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-sliders-h" style="color: var(--neon-green);"></i> Limites</h3>
            <div class="form-group">
                <label class="form-label"><?php echo $configs['max_anuncios_usuario']['label']; ?></label>
                <input type="number" name="max_anuncios_usuario" class="form-input" min="1"
                       value="<?php echo sanitize($configs['max_anuncios_usuario']['valor']); ?>">
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;"><?php echo $configs['max_anuncios_usuario']['desc']; ?></p>
            </div>
        </div>

        <!-- Manutencao -->
        <div class="card" style="margin-bottom: 20px; border-left: 3px solid var(--neon-yellow);">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-wrench" style="color: var(--neon-yellow);"></i> Modo Manutencao</h3>
            <div class="form-group">
                <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                    <input type="checkbox" name="manutencao" value="1"
                           <?php echo intval($configs['manutencao']['valor']) ? 'checked' : ''; ?>
                           style="width: 20px; height: 20px; accent-color: var(--neon-green);">
                    <span style="color: var(--text); font-weight: 600;">Ativar modo manutencao</span>
                </label>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;"><?php echo $configs['manutencao']['desc']; ?></p>
            </div>
            <div class="form-group">
                <label class="form-label">Mensagem de Manutencao</label>
                <textarea name="mensagem_manutencao" class="form-input" rows="3"><?php echo sanitize($configs['mensagem_manutencao']['valor']); ?></textarea>
            </div>
        </div>

        <!-- Contato -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-headset" style="color: var(--neon-green);"></i> Contato / Suporte</h3>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-whatsapp" style="color: #25D366;"></i> <?php echo $configs['whatsapp_suporte']['label']; ?></label>
                    <input type="text" name="whatsapp_suporte" class="form-input" placeholder="5511999999999"
                           value="<?php echo sanitize($configs['whatsapp_suporte']['valor']); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label"><i class="fab fa-telegram" style="color: #0088CC;"></i> <?php echo $configs['telegram_grupo']['label']; ?></label>
                    <input type="url" name="telegram_grupo" class="form-input" placeholder="https://t.me/seugrupo"
                           value="<?php echo sanitize($configs['telegram_grupo']['valor']); ?>">
                </div>
            </div>
        </div>

        <button type="submit" class="btn btn-primary btn-lg">
            <i class="fas fa-save"></i> Salvar Configuracoes
        </button>
    </form>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>