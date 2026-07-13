<?php
/**
 * Asgard Store - Notificacoes do Usuario
 */

$page_title = 'Notificacoes';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];

// Marcar notificacao como lida via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['acao'])) {
    require_csrf();

    if ($_POST['acao'] === 'marcar_lida') {
        $notif_id = intval($_POST['notificacao_id'] ?? 0);
        if ($notif_id > 0) {
            db_update('notificacoes', ['lida' => 1], 'id = ? AND usuario_id = ?', [$notif_id, $user_id]);
        }
    } elseif ($_POST['acao'] === 'marcar_todas_lidas') {
        db_update('notificacoes', ['lida' => 1], 'usuario_id = ? AND lida = 0', [$user_id]);
    }

    header('Location: ' . SITE_URL . '/painel/notificacoes.php');
    exit;
}

// Filtro por tipo
$filtro_tipo = $_GET['tipo'] ?? '';
$where = 'n.usuario_id = ?';
$params = [$user_id];

if ($filtro_tipo && in_array($filtro_tipo, ['compra', 'venda', 'sistema', 'suporte'])) {
    $where .= ' AND n.tipo = ?';
    $params[] = $filtro_tipo;
}

// Contagem total e nao lidas
$total_notifs = db_count('notificacoes', 'usuario_id = ?', [$user_id]);
$nao_lidas = db_count('notificacoes', 'usuario_id = ? AND lida = 0', [$user_id]);

// Paginacao
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 20;
$total = db_count('notificacoes', $where, $params);
$pagination = paginate($total, $per_page, $page);

// Buscar notificacoes
$notificacoes = db_fetch_all(
    "SELECT n.* FROM notificacoes n 
     WHERE {$where} 
     ORDER BY n.lida ASC, n.criado_em DESC 
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px; max-width: 800px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <h1 class="section-title" style="margin: 0;">
            <i class="fas fa-bell" style="color: var(--neon-green);"></i> Notificacoes
            <?php if ($nao_lidas > 0): ?>
                <span style="background: var(--neon-pink); color: #fff; font-size: 0.7rem; padding: 2px 8px; border-radius: 10px; vertical-align: middle;">
                    <?php echo $nao_lidas; ?>
                </span>
            <?php endif; ?>
        </h1>

        <?php if ($nao_lidas > 0): ?>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="acao" value="marcar_todas_lidas">
            <button type="submit" class="btn btn-secondary" style="font-size: 0.85rem;">
                <i class="fas fa-check-double"></i> Marcar todas como lidas
            </button>
        </form>
        <?php endif; ?>
    </div>

    <!-- Filtros -->
    <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="/painel/notificacoes.php" 
           class="btn <?php echo empty($filtro_tipo) ? 'btn-primary' : 'btn-secondary'; ?>" 
           style="font-size: 0.85rem; padding: 6px 14px;">Todas</a>
        <a href="/painel/notificacoes.php?tipo=compra" 
           class="btn <?php echo $filtro_tipo === 'compra' ? 'btn-primary' : 'btn-secondary'; ?>" 
           style="font-size: 0.85rem; padding: 6px 14px;">Compras</a>
        <a href="/painel/notificacoes.php?tipo=venda" 
           class="btn <?php echo $filtro_tipo === 'venda' ? 'btn-primary' : 'btn-secondary'; ?>" 
           style="font-size: 0.85rem; padding: 6px 14px;">Vendas</a>
        <a href="/painel/notificacoes.php?tipo=sistema" 
           class="btn <?php echo $filtro_tipo === 'sistema' ? 'btn-primary' : 'btn-secondary'; ?>" 
           style="font-size: 0.85rem; padding: 6px 14px;">Sistema</a>
        <a href="/painel/notificacoes.php?tipo=suporte" 
           class="btn <?php echo $filtro_tipo === 'suporte' ? 'btn-primary' : 'btn-secondary'; ?>" 
           style="font-size: 0.85rem; padding: 6px 14px;">Suporte</a>
    </div>

    <!-- Lista de Notificacoes -->
    <?php if (empty($notificacoes)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <i class="fas fa-bell-slash" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px; display: block;"></i>
            <p style="color: var(--text-muted);">Nenhuma notificacao encontrada.</p>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($notificacoes as $n): ?>
                <div class="card" style="padding: 15px 20px; <?php echo !$n['lida'] ? 'border-left: 3px solid var(--neon-green); background: rgba(0,255,136,0.03);' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 15px;">
                        <div style="flex: 1;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                                <?php
                                $tipo_icons = [
                                    'compra' => ['fas fa-shopping-cart', 'var(--neon-blue)'],
                                    'venda' => ['fas fa-hand-holding-usd', 'var(--neon-green)'],
                                    'sistema' => ['fas fa-cog', 'var(--text-muted)'],
                                    'suporte' => ['fas fa-headset', 'var(--neon-purple)'],
                                ];
                                $icon = $tipo_icons[$n['tipo']] ?? ['fas fa-bell', 'var(--text-muted)'];
                                ?>
                                <i class="<?php echo $icon[0]; ?>" style="color: <?php echo $icon[1]; ?>;"></i>
                                <strong style="color: var(--text); font-size: 0.95rem;">
                                    <?php echo sanitize($n['titulo']); ?>
                                </strong>
                                <?php if (!$n['lida']): ?>
                                    <span style="width: 8px; height: 8px; background: var(--neon-green); border-radius: 50%; display: inline-block;"></span>
                                <?php endif; ?>
                            </div>
                            <p style="color: var(--text-muted); font-size: 0.85rem; margin: 0 0 5px 0;">
                                <?php echo sanitize($n['mensagem']); ?>
                            </p>
                            <span style="color: var(--text-muted); font-size: 0.75rem;">
                                <?php echo time_ago($n['criado_em']); ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 5px; flex-shrink: 0;">
                            <?php if (!empty($n['link'])): ?>
                                <a href="<?php echo sanitize($n['link']); ?>" 
                                   style="background: var(--neon-green); color: #000; padding: 6px 12px; border-radius: 6px; text-decoration: none; font-size: 0.8rem; font-weight: 600;">
                                    <i class="fas fa-eye"></i>
                                </a>
                            <?php endif; ?>
                            <?php if (!$n['lida']): ?>
                            <form method="POST" style="margin: 0;">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="acao" value="marcar_lida">
                                <input type="hidden" name="notificacao_id" value="<?php echo $n['id']; ?>">
                                <button type="submit" 
                                        style="background: var(--bg-card); border: 1px solid var(--border-color); color: var(--text-muted); padding: 6px 10px; border-radius: 6px; cursor: pointer; font-size: 0.8rem;"
                                        title="Marcar como lida">
                                    <i class="fas fa-check"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginacao -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php
            $base_url = '/painel/notificacoes.php?' . ($filtro_tipo ? "tipo={$filtro_tipo}&" : '');
            echo render_pagination($pagination, $base_url);
            ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>