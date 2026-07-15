<?php
/**
 * Asgard Store - Admin: Gerenciar Jogos
 */

$page_title = 'Gerenciar Jogos';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

// Criar jogo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'criar') {
    require_csrf();
    $nome = sanitize($_POST['nome'] ?? '');
    $slug = slugify($nome);
    $moeda_nome = sanitize($_POST['moeda_nome'] ?? '');
    $icone = sanitize($_POST['icone'] ?? 'default-game.png');

    if (empty($nome)) {
        $error = 'Nome do jogo e obrigatorio.';
    } elseif (db_count('jogos', 'slug = ?', [$slug]) > 0) {
        $error = 'Ja existe um jogo com esse nome.';
    } else {
        db_insert('jogos', [
            'nome' => $nome,
            'slug' => $slug,
            'icone' => $icone,
            'moeda_nome' => $moeda_nome,
            'ativo' => 1,
            'ordem' => db_count('jogos') + 1,
        ]);
        db_insert('admin_log', [
            'admin_id' => $_SESSION['user_id'],
            'acao' => 'criar_jogo',
            'descricao' => "Jogo criado: {$nome}",
            'tipo' => 'jogos',
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Jogo criado com sucesso!'];
        header('Location: ' . SITE_URL . '/admin/jogos.php');
        exit;
    }
}

// Editar jogo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'editar') {
    require_csrf();
    $id = intval($_POST['id'] ?? 0);
    $nome = sanitize($_POST['nome'] ?? '');
    $moeda_nome = sanitize($_POST['moeda_nome'] ?? '');
    $icone = sanitize($_POST['icone'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;
    $ordem = intval($_POST['ordem'] ?? 0);

    if ($id > 0 && !empty($nome)) {
        $data = [
            'nome' => $nome,
            'slug' => slugify($nome),
            'moeda_nome' => $moeda_nome,
            'icone' => $icone ?: 'default-game.png',
            'ativo' => $ativo,
            'ordem' => $ordem,
        ];
        db_update('jogos', $data, 'id = ?', [$id]);
        db_insert('admin_log', [
            'admin_id' => $_SESSION['user_id'],
            'acao' => 'editar_jogo',
            'descricao' => "Jogo #{$id} atualizado",
            'tipo' => 'jogos',
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Jogo atualizado!'];
        header('Location: ' . SITE_URL . '/admin/jogos.php');
        exit;
    }
}

// Toggle ativo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'toggle') {
    require_csrf();
    $id = intval($_POST['id'] ?? 0);
    $jogo = db_fetch("SELECT ativo FROM jogos WHERE id = ?", [$id]);
    if ($jogo) {
        db_update('jogos', ['ativo' => $jogo['ativo'] ? 0 : 1], 'id = ?', [$id]);
    }
    header('Location: ' . SITE_URL . '/admin/jogos.php');
    exit;
}

// Deletar jogo
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'deletar') {
    require_csrf();
    $id = intval($_POST['id'] ?? 0);
    if ($id > 0) {
        $anuncios = db_count('anuncios', 'jogo_id = ?', [$id]);
        if ($anuncios > 0) {
            $error = "Nao e possivel excluir: {$anuncios} anuncio(s) vinculado(s).";
        } else {
            db_delete('categorias', 'jogo_id = ?', [$id]);
            db_delete('jogos', 'id = ?', [$id]);
            db_insert('admin_log', [
                'admin_id' => $_SESSION['user_id'],
                'acao' => 'deletar_jogo',
                'descricao' => "Jogo #{$id} excluido",
                'tipo' => 'jogos',
            ]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Jogo excluido!'];
        }
        header('Location: ' . SITE_URL . '/admin/jogos.php');
        exit;
    }
}

// Buscar jogos
$jogos = db_fetch_all("SELECT j.*, (SELECT COUNT(*) FROM anuncios WHERE jogo_id = j.id) as total_anuncios FROM jogos j ORDER BY j.ordem ASC, j.nome ASC");
$total_jogos = count($jogos);
$jogos_ativos = db_count('jogos', 'ativo = 1');

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-gamepad" style="color: var(--neon-green);"></i> Gerenciar Jogos
    </h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 30px;">
        <div class="card" style="text-align: center; padding: 15px;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--text);"><?php echo $total_jogos; ?></div>
            <div style="color: var(--text-muted); font-size: 0.85rem;">Total</div>
        </div>
        <div class="card" style="text-align: center; padding: 15px;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neon-green);"><?php echo $jogos_ativos; ?></div>
            <div style="color: var(--text-muted); font-size: 0.85rem;">Ativos</div>
        </div>
    </div>

    <!-- Formulario Criar -->
    <div class="card" style="margin-bottom: 30px; border-left: 3px solid var(--neon-green);">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-plus-circle" style="color: var(--neon-green);"></i> Novo Jogo</h3>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="acao" value="criar">
            <div style="display: grid; grid-template-columns: 2fr 1fr 1fr auto; gap: 10px; align-items: end;">
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Nome *</label>
                    <input type="text" name="nome" class="form-input" required placeholder="Nome do jogo">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Moeda do Jogo</label>
                    <input type="text" name="moeda_nome" class="form-input" placeholder="Ex: Gems, Coins">
                </div>
                <div class="form-group" style="margin: 0;">
                    <label class="form-label">Icone (nome arquivo)</label>
                    <input type="text" name="icone" class="form-input" placeholder="default-game.png">
                </div>
                <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Criar</button>
            </div>
        </form>
    </div>

    <!-- Lista de Jogos -->
    <div class="card">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-list" style="color: var(--neon-blue);"></i> Jogos Cadastrados (<?php echo $total_jogos; ?>)</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 2px solid var(--border-color);">
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">#</th>
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Jogo</th>
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Moeda</th>
                        <th style="text-align: center; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Anuncios</th>
                        <th style="text-align: center; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Ordem</th>
                        <th style="text-align: center; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Status</th>
                        <th style="text-align: right; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Acoes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($jogos as $j): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 10px; color: var(--text-muted);"><?php echo $j['id']; ?></td>
                        <td style="padding: 10px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <img src="/assets/img/games/<?php echo $j['icone']; ?>" style="width: 28px; height: 28px; border-radius: 4px; object-fit: cover;" onerror="this.style.display='none'">
                                <div>
                                    <strong style="color: var(--text);"><?php echo sanitize($j['nome']); ?></strong>
                                    <div style="color: var(--text-muted); font-size: 0.75rem;"><?php echo $j['slug']; ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 10px; color: var(--text-muted);"><?php echo sanitize($j['moeda_nome'] ?? '-'); ?></td>
                        <td style="padding: 10px; text-align: center; color: var(--neon-blue); font-weight: 600;"><?php echo $j['total_anuncios']; ?></td>
                        <td style="padding: 10px; text-align: center; color: var(--text-muted);"><?php echo $j['ordem']; ?></td>
                        <td style="padding: 10px; text-align: center;">
                            <form method="POST" style="display: inline;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="acao" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $j['id']; ?>">
                                <button type="submit" style="background: none; border: none; cursor: pointer; padding: 4px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; <?php echo $j['ativo'] ? 'color: var(--neon-green); background: rgba(0,255,136,0.1);' : 'color: var(--neon-pink); background: rgba(255,107,107,0.1);'; ?>">
                                    <?php echo $j['ativo'] ? '● Ativo' : '○ Inativo'; ?>
                                </button>
                            </form>
                        </td>
                        <td style="padding: 10px; text-align: right;">
                            <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Excluir este jogo?')">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="acao" value="deletar">
                                <input type="hidden" name="id" value="<?php echo $j['id']; ?>">
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
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>