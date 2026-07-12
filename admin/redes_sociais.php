<?php
/**
 * Asgard Store - Admin: Gerenciar Redes Sociais
 */

$page_title = 'Gerenciar Redes Sociais';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

// Processar acoes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $acao = $_POST['acao'] ?? '';
    
    if ($acao === 'salvar') {
        $id = intval($_POST['id'] ?? 0);
        $url = sanitize($_POST['url'] ?? '');
        $ativo = isset($_POST['ativo']) ? 1 : 0;
        $ordem = intval($_POST['ordem'] ?? 0);
        
        if ($id > 0) {
            db_update('redes_sociais', [
                'url' => $url,
                'ativo' => $ativo,
                'ordem' => $ordem
            ], 'id = ?', [$id]);
            $success = 'Rede social atualizada com sucesso!';
        }
    }
}

// Buscar redes sociais
$redes = db_fetch_all("SELECT * FROM redes_sociais ORDER BY ordem ASC");

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 class="section-title">Redes Sociais</h1>
        <a href="/admin/" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Voltar</a>
    </div>
    
    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>
    
    <p style="color: var(--text-secondary); margin-bottom: 25px;">
        Configure as redes sociais exibidas no rodape do site. O admin pode ativar/desativar cada uma.
    </p>
    
    <div class="table-container">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Rede</th>
                    <th>URL</th>
                    <th>Ordem</th>
                    <th>Status</th>
                    <th>Acoes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($redes as $rede): ?>
                <tr>
                    <td>
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <i class="<?php echo sanitize($rede['icone']); ?>" style="font-size: 1.3rem; color: <?php echo sanitize($rede['cor'] ?? 'var(--neon-green)'); ?>;"></i>
                            <strong><?php echo sanitize($rede['nome']); ?></strong>
                        </div>
                    </td>
                    <td>
                        <form method="POST" style="display: flex; gap: 8px; align-items: center;">
                            <?php echo csrf_field(); ?>
                            <input type="hidden" name="acao" value="salvar">
                            <input type="hidden" name="id" value="<?php echo $rede['id']; ?>">
                            <input type="url" name="url" class="form-input" value="<?php echo sanitize($rede['url']); ?>" placeholder="https://..." style="min-width: 250px;">
                    </td>
                    <td>
                        <input type="number" name="ordem" class="form-input" value="<?php echo $rede['ordem']; ?>" style="width: 60px; text-align: center;">
                    </td>
                    <td>
                        <label class="checkbox-label">
                            <input type="checkbox" name="ativo" value="1" <?php echo $rede['ativo'] ? 'checked' : ''; ?> style="width: auto;">
                            <span><?php echo $rede['ativo'] ? 'Ativo' : 'Inativo'; ?></span>
                        </label>
                    </td>
                    <td>
                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-save"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div class="card" style="margin-top: 30px;">
        <h4 style="margin-bottom: 15px;"><i class="fas fa-info-circle" style="color: var(--neon-blue);"></i> Como Funciona</h4>
        <ul style="list-style: none; padding: 0; color: var(--text-secondary);">
            <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--neon-green); margin-right: 10px;"></i> As redes ativas aparecem no rodape do site</li>
            <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--neon-green); margin-right: 10px;"></i> A ordem define a posicao de exibicao</li>
            <li style="padding: 8px 0;"><i class="fas fa-check" style="color: var(--neon-green); margin-right: 10px;"></i> Desative uma rede para ocultar do site</li>
        </ul>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
