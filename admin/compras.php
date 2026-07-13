<?php
/**
 * Asgard Store - Admin: Gerenciar Compras
 */

$page_title = 'Gerenciar Compras';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

// Acoes admin
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $acao = $_POST['acao'] ?? '';
    $id = intval($_POST['id'] ?? 0);

    $compra = db_fetch("SELECT * FROM compras WHERE id = ?", [$id]);
    if (!$compra) {
        $error = 'Compra nao encontrada.';
        header('Location: ' . SITE_URL . '/admin/compras.php');
        exit;
    }

    if ($acao === 'confirmar_pagamento') {
        db_update('compras', ['status' => 'pagamento_confirmado'], 'id = ?', [$id]);
        create_notification($compra['vendedor_id'], 'Pagamento Confirmado', "Pagamento da compra #{$id} confirmado pelo admin.", 'venda', '/painel/vendas.php');
        db_insert('admin_log', ['admin_id' => $_SESSION['user_id'], 'acao' => 'confirmar_pagamento', 'descricao' => "Compra #{$id} - pagamento confirmado", 'tipo' => 'compras']);
        $success = "Pagamento da compra #{$id} confirmado!";
    } elseif ($acao === 'forcar_entrega') {
        db_update('compras', ['status' => 'concluido', 'confirmado_comprador' => 1, 'confirmado_vendedor' => 1], 'id = ?', [$id]);
        db_query("UPDATE usuarios SET saldo = saldo + ?, total_vendas = total_vendas + 1 WHERE id = ?", [$compra['valor_vendedor'], $compra['vendedor_id']]);
        create_notification($compra['comprador_id'], 'Compra Concluida', "Compra #{$id} concluida pelo admin.", 'compra', '/painel/compras.php');
        create_notification($compra['vendedor_id'], 'Venda Concluida', "Venda #{$id} concluida pelo admin. Saldo creditado.", 'venda', '/painel/vendas.php');
        db_insert('admin_log', ['admin_id' => $_SESSION['user_id'], 'acao' => 'forcar_conclusao', 'descricao' => "Compra #{$id} - conclusao forçada", 'tipo' => 'compras']);
        $success = "Compra #{$id} concluida e saldo creditado ao vendedor!";
    } elseif ($acao === 'cancelar') {
        db_update('compras', ['status' => 'cancelado'], 'id = ?', [$id]);
        db_insert('admin_log', ['admin_id' => $_SESSION['user_id'], 'acao' => 'cancelar_compra', 'descricao' => "Compra #{$id} cancelada", 'tipo' => 'compras']);
        $success = "Compra #{$id} cancelada.";
    }

    header('Location: ' . SITE_URL . '/admin/compras.php');
    exit;
}

// Filtros
$filtro_status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));

$where = '1=1';
$params = [];
if ($filtro_status && in_array($filtro_status, ['aguardando_pagamento', 'pagamento_confirmado', 'em_entrega', 'concluido', 'cancelado', 'disputa'])) {
    $where .= ' AND c.status = ?';
    $params[] = $filtro_status;
}

$total = db_count('compras c', $where, $params);
$pagination = paginate($total, 20, $page);

$compras = db_fetch_all(
    "SELECT c.*, a.titulo as anuncio_titulo, a.preco,
            u_comp.nome as comprador_nome, u_vend.nome as vendedor_nome
     FROM compras c
     JOIN anuncios a ON c.anuncio_id = a.id
     JOIN usuarios u_comp ON c.comprador_id = u_comp.id
     JOIN usuarios u_vend ON c.vendedor_id = u_vend.id
     WHERE {$where}
     ORDER BY c.criado_em DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

// Stats
$total_compras = db_count('compras');
$pendentes = db_count('compras', "status IN ('aguardando_pagamento', 'pagamento_confirmado', 'em_entrega')");
$valor_total = db_fetch("SELECT COALESCE(SUM(valor), 0) as total FROM compras WHERE status = 'concluido'")['total'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-shopping-cart" style="color: var(--neon-green);"></i> Gerenciar Compras
    </h1>

    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div class="card" style="text-align: center; padding: 15px;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--text);"><?php echo $total_compras; ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">Total</div>
        </div>
        <div class="card" style="text-align: center; padding: 15px;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neon-yellow);"><?php echo $pendentes; ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">Andamento</div>
        </div>
        <div class="card" style="text-align: center; padding: 15px;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neon-green);">R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">Faturamento</div>
        </div>
    </div>

    <!-- Filtros -->
    <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="?" class="btn <?php echo empty($filtro_status) ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 0.85rem;">Todas</a>
        <?php foreach (['aguardando_pagamento', 'pagamento_confirmado', 'em_entrega', 'concluido', 'cancelado', 'disputa'] as $f): ?>
            <a href="?status=<?php echo $f; ?>" class="btn <?php echo $filtro_status === $f ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 0.85rem;">
                <?php echo str_replace('_', ' ', ucfirst($f)); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Lista -->
    <?php if (empty($compras)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <p style="color: var(--text-muted);">Nenhuma compra encontrada.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <div class="card">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color);">
                            <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">#</th>
                            <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Anuncio</th>
                            <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Comprador</th>
                            <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Vendedor</th>
                            <th style="text-align: right; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Valor</th>
                            <th style="text-align: center; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Status</th>
                            <th style="text-align: right; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($compras as $c): ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 10px; color: var(--text-muted);"><?php echo $c['id']; ?></td>
                            <td style="padding: 10px; color: var(--text); font-size: 0.85rem;"><?php echo sanitize($c['anuncio_titulo']); ?></td>
                            <td style="padding: 10px; color: var(--text-muted); font-size: 0.85rem;"><?php echo sanitize($c['comprador_nome']); ?></td>
                            <td style="padding: 10px; color: var(--text-muted); font-size: 0.85rem;"><?php echo sanitize($c['vendedor_nome']); ?></td>
                            <td style="padding: 10px; text-align: right; color: var(--neon-green); font-weight: 600;">R$ <?php echo number_format($c['valor'], 2, ',', '.'); ?></td>
                            <td style="padding: 10px; text-align: center;">
                                <?php
                                $s_colors = [
                                    'aguardando_pagamento' => 'var(--neon-yellow)',
                                    'pagamento_confirmado' => 'var(--neon-blue)',
                                    'em_entrega' => 'var(--neon-purple)',
                                    'concluido' => 'var(--neon-green)',
                                    'cancelado' => 'var(--text-muted)',
                                    'disputa' => 'var(--neon-pink)',
                                ];
                                $sc = $s_colors[$c['status']] ?? 'var(--text-muted)';
                                ?>
                                <span style="color: <?php echo $sc; ?>; font-size: 0.8rem; font-weight: 600;">
                                    <?php echo str_replace('_', ' ', ucfirst($c['status'])); ?>
                                </span>
                            </td>
                            <td style="padding: 10px; text-align: right;">
                                <div style="display: flex; gap: 4px; justify-content: flex-end;">
                                    <?php if ($c['status'] === 'aguardando_pagamento'): ?>
                                    <form method="POST" style="margin: 0;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="acao" value="confirmar_pagamento">
                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" class="btn btn-primary" style="padding: 4px 10px; font-size: 0.75rem;" title="Confirmar Pagamento">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (in_array($c['status'], ['pagamento_confirmado', 'em_entrega'])): ?>
                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Forcar conclusao? O saldo sera creditado ao vendedor.')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="acao" value="forcar_entrega">
                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem; color: var(--neon-green); border-color: var(--neon-green);" title="Forcar Conclusao">
                                            <i class="fas fa-gavel"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <?php if (!in_array($c['status'], ['concluido', 'cancelado'])): ?>
                                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Cancelar esta compra?')">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="acao" value="cancelar">
                                        <input type="hidden" name="id" value="<?php echo $c['id']; ?>">
                                        <button type="submit" class="btn btn-secondary" style="padding: 4px 10px; font-size: 0.75rem; color: var(--neon-pink); border-color: var(--neon-pink);" title="Cancelar">
                                            <i class="fas fa-ban"></i>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php echo render_pagination($pagination, "?status={$filtro_status}&"); ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>