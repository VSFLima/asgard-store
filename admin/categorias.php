<?php
/**
 * Asgard Store - Admin: Gerenciar Categorias
 */

$page_title = 'Gerenciar Categorias';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

// Criar categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar') {
    require_csrf();
    $jogo_id = intval($_POST['jogo_id'] ?? 0);
    $nome = sanitize($_POST['nome'] ?? '');

    if ($jogo_id <= 0 || empty($nome)) {
        $error = 'Jogo e nome sao obrigatorios.';
    } else {
        db_insert('categorias', [
            'jogo_id' => $jogo_id,
            'nome' => $nome,
            'slug' => slugify($nome),
        ]);
        db_insert('admin_log', [
            'admin_id' => $_SESSION['user_id'],
            'acao' => 'criar_categoria',
            'descricao' => "Categoria criada: {$nome}",
            'tipo' => 'categorias',
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Categoria criada!'];
        header('Location: ' . SITE_URL . '/admin/categorias.php');
        exit;
    }
}

// Deletar categoria
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'deletar') {
    require_csrf();
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        db_delete('categorias', 'id = ?', [$id]);
        db_insert('admin_log', [
            'admin_id' => $_SESSION['user_id'],
            'acao' => 'deletar_categoria',
            'descricao' => "Categoria #{$id} excluida",
            'tipo' => 'categorias',
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Categoria excluida!'];
    }
    header('Location: ' . SITE_URL . '/admin/categorias.php');
    exit;
}

// Buscar dados
$jogos = db_fetch_all("SELECT * FROM jogos WHERE ativo = 1 ORDER BY nome ASC");
$categorias = db_fetch_all(
    "SELECT c.*, j.nome as jogo_nome 
     FROM categorias c 
     JOIN jogos j ON c.jogo_id = j.id 
     ORDER BY j.nome ASC, c.nome ASC"
);
$total = count($categorias);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-tags" style="color: var(--neon-green);"></i> Gerenciar Categorias
    </h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Criar Categoria -->
    <div class="card" style="margin-bottom: 30px; border-left: 3px solid var(--neon-green);">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-plus-circle" style="color: var(--neon-green);"></i> Nova Categoria</h3>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="acao" value="criar">
            <div style="display: grid; grid-template-columns: 2fr 2fr auto; gap: 10px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Jogo *</label>
                    <select name="jogo_id" class="form-select" required>
                        <option value="">Selecione o jogo</option>
                        <?php foreach ($jogos as $j): ?>
                            <option value="<?php echo $j['id']; ?>"><?php echo sanitize($j['nome']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Nome da Categoria *</label>
                    <input type="text" name="nome" class="form-input" required placeholder="Ex: Contas, Itens, Servicos">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Criar</button>
            </div>
        </form>
    </div>

    <!-- Lista -->
    <div class="card">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-list" style="color: var(--neon-blue);"></i> Categorias (<?php echo $total; ?>)</h3>
        <?php if (empty($categorias)): ?>
            <p style="color: var(--text-muted); text-align: center; padding: 20px;">Nenhuma categoria cadastrada.</p>
        <?php else: ?>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">ID</th>
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Categoria</th>
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Jogo</th>
                        <th style="text-align: right; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $c): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 10px; color: var(--text-muted);"><?php echo $c['id']; ?></td>
                        <td style="padding: 10px; color: var(--text); font-weight: 600;"><?php echo sanitize($c['nome']); ?></td>
                        <td style="padding: 10px;">
                            <span style="background: rgba(0,255,136,0.1); color: var(--neon-green); padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">
                                <?php echo sanitize($c['jogo_nome']); ?>
                            </span>
                        </td>
                        <td style="padding: 10px; text-align: right;">
                            <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Excluir esta categoria?')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="acao" value="deletar">
                                <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                <button type="submit" style="background: none; border: none; color: var(--neon-pink); cursor: pointer; padding: 5px;" title="Excluir">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>