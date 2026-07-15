<?php
/**
 * Asgard Store - Admin: Gerenciar Creditos
 */

$page_title = 'Gerenciar Creditos';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

// Criar pacote
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar') {
    require_csrf();
    $jogo_id = intval($_POST['jogo_id'] ?? 0);
    $nome = sanitize($_POST['nome'] ?? '');
    $descricao = sanitize($_POST['descricao'] ?? '');
    $quantidade = intval($_POST['quantidade'] ?? 0);
    $moeda_jogo = sanitize($_POST['moeda_jogo'] ?? '');
    $preco_original = floatval($_POST['preco_original'] ?? 0);
    $desconto = floatval($_POST['desconto_percentual'] ?? 0);
    $preco_final = $preco_original - ($preco_original * $desconto / 100);
    $estoque = intval($_POST['estoque'] ?? 0);

    if ($jogo_id <= 0 || empty($nome) || $preco_original <= 0) {
        $error = 'Preencha todos os campos obrigatorios.';
    } else {
        db_insert('creditos', [
            'jogo_id' => $jogo_id,
            'nome' => $nome,
            'descricao' => $descricao,
            'quantidade' => $quantidade,
            'moeda_jogo' => $moeda_jogo,
            'preco_original' => $preco_original,
            'desconto_percentual' => $desconto,
            'preco_final' => $preco_final,
            'estoque' => $estoque,
            'ativo' => 1,
            'ordem' => db_count('creditos') + 1,
        ]);
        db_insert('admin_log', [
            'admin_id' => $_SESSION['user_id'],
            'acao' => 'criar_credito',
            'descricao' => "Credito criado: {$nome}",
            'tipo' => 'creditos',
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pacote de creditos criado!'];
        header('Location: ' . SITE_URL . '/admin/creditos.php');
        exit;
    }
}

// Toggle ativo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'toggle') {
    require_csrf();
    $id = intval($_POST['id'] ?? 0);
    $credito = db_fetch("SELECT ativo FROM creditos WHERE id = ?", [$id]);
    if ($credito) {
        db_update('creditos', ['ativo' => $credito['ativo'] ? 0 : 1], 'id = ?', [$id]);
    }
    header('Location: ' . SITE_URL . '/admin/creditos.php');
    exit;
}

// Deletar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'deletar') {
    require_csrf();
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        db_delete('creditos', 'id = ?', [$id]);
        db_insert('admin_log', [
            'admin_id' => $_SESSION['user_id'],
            'acao' => 'deletar_credito',
            'descricao' => "Credito #{$id} excluido",
            'tipo' => 'creditos',
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Pacote excluido!'];
    }
    header('Location: ' . SITE_URL . '/admin/creditos.php');
    exit;
}

$jogos = db_fetch_all("SELECT * FROM jogos WHERE ativo = 1 ORDER BY nome ASC");
$creditos = db_fetch_all(
    "SELECT c.*, j.nome as jogo_nome 
     FROM creditos c 
     JOIN jogos j ON c.jogo_id = j.id 
     ORDER BY j.nome ASC, c.ordem ASC"
);
$total = count($creditos);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-coins" style="color: var(--neon-green);"></i> Gerenciar Creditos
    </h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Criar Pacote -->
    <div class="card" style="margin-bottom: 30px; border-left: 3px solid var(--neon-green);">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-plus-circle" style="color: var(--neon-green);"></i> Novo Pacote</h3>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="acao" value="criar">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Jogo *</label>
                    <select name="jogo_id" class="form-select" required>
                        <option value="">Selecione</option>
                        <?php foreach ($jogos as $j): ?>
                            <option value="<?php echo $j['id']; ?>"><?php echo sanitize($j['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Nome do Pacote *</label>
                    <input type="text" name="nome" class="form-input" required placeholder="Ex: 1000 Gems">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 10px; margin-bottom: 10px;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Quantidade</label>
                    <input type="number" name="quantidade" class="form-input" placeholder="1000">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Moeda do Jogo</label>
                    <input type="text" name="moeda_jogo" class="form-input" placeholder="Gems">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Estoque</label>
                    <input type="number" name="estoque" class="form-input" placeholder="100">
                </div>
            </div>
            <div style="display: grid; grid-template-columns: 1fr 1fr 2fr; gap: 10px; margin-bottom: 10px;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Preco Original (R$) *</label>
                    <input type="number" name="preco_original" class="form-input" required step="0.01" placeholder="29.90">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Desconto (%)</label>
                    <input type="number" name="desconto_percentual" class="form-input" step="0.1" max="100" placeholder="10">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Descricao</label>
                    <input type="text" name="descricao" class="form-input" placeholder="Descricao opcional">
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Criar Pacote</button>
        </form>
    </div>

    <!-- Lista -->
    <div class="card">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-list" style="color: var(--neon-blue);"></i> Pacotes (<?php echo $total; ?>)</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Jogo</th>
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Pacote</th>
                        <th style="text-align: right; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Preco</th>
                        <th style="text-align: center; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Estoque</th>
                        <th style="text-align: center; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Status</th>
                        <th style="text-align: right; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($creditos as $c): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 10px; color: var(--neon-green); font-size: 0.85rem;"><?php echo sanitize($c['jogo_nome']); ?></td>
                        <td style="padding: 10px;">
                            <strong style="color: var(--text);"><?php echo sanitize($c['nome']); ?></strong>
                            <div style="color: var(--text-muted); font-size: 0.75rem;"><?php echo number_format($c['quantidade']) . ' ' . sanitize($c['moeda_jogo'] ?? ''); ?></div>
                        </td>
                        <td style="padding: 10px; text-align: right;">
                            <?php if ($c['desconto_percentual'] > 0): ?>
                                <span style="color: var(--text-muted); text-decoration: line-through; font-size: 0.8rem;">R$ <?php echo number_format($c['preco_original'], 2, ',', '.'); ?></span><br>
                            <?php endif; ?>
                            <strong style="color: var(--neon-green);">R$ <?php echo number_format($c['preco_final'], 2, ',', '.'); ?></strong>
                        </td>
                        <td style="padding: 10px; text-align: center; color: <?php echo $c['estoque'] <= 0 ? 'var(--neon-pink)' : 'var(--text)'; ?>;">
                            <?php echo $c['estoque']; ?>
                        </td>
                        <td style="padding: 10px; text-align: center;">
                            <form method="POST" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="acao" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                <button type="submit" style="background: none; border: none; cursor: pointer; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; <?php echo $c['ativo'] ? 'color: var(--neon-green); background: rgba(0,255,136,0.1);' : 'color: var(--neon-pink); background: rgba(255,107,107,0.1);'; ?>">
                                    <?php echo $c['ativo'] ? '● Ativo' : '○ Inativo'; ?>
                                </button>
                            </form>
                        </td>
                        <td style="padding: 10px; text-align: right;">
                            <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Excluir este pacote?')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="acao" value="deletar">
                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                <button type="submit" style="background: none; border: none; color: var(--neon-pink); cursor: pointer; padding: 5px;">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>