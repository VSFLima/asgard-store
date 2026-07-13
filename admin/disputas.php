<?php
/**
 * Asgard Store - Admin: Gerenciar Disputas
 */

$page_title = 'Gerenciar Disputas';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

// Resolver disputa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'resolver') {
    require_csrf();
    $id = intval($_POST['id'] ?? 0);
    $resolucao = sanitize($_POST['resolucao'] ?? '');
    $resultado = $_POST['resultado'] ?? 'favor_comprador';

    $disputa = db_fetch("SELECT d.*, c.comprador_id, c.vendedor_id, c.valor, c.valor_vendedor, c.status as compra_status FROM disputas d JOIN compras c ON d.compra_id = c.id WHERE d.id = ? AND d.status = 'aberta'", [$id]);

    if ($disputa && !empty($resolucao)) {
        $admin_id = $_SESSION['user_id'];

        db_update('disputas', [
            'status' => 'resolvida',
            'resolucao' => $resolucao,
            'resolvido_por' => $admin_id,
            'resolvido_em' => date('Y-m-d H:i:s'),
        ], 'id = ?', [$id]);

        if ($resultado === 'favor_comprador') {
            // Estornar ao comprador
            db_query("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?", [$disputa['valor'], $disputa['comprador_id']]);
            db_update('compras', ['status' => 'cancelado'], 'id = ?', [$disputa['compra_id']]);
            create_notification($disputa['comprador_id'], 'Disputa Resolvida', "Sua disputa foi resolvida a seu favor. Valor estornado.", 'compra', '/painel/compras.php');
            create_notification($disputa['vendedor_id'], 'Disputa Resolvida', "Disputa resolvida a favor do comprador.", 'venda', '/painel/vendas.php');
        } else {
            // Favor vendedor - venda concluida
            db_query("UPDATE usuarios SET saldo = saldo + ?, total_vendas = total_vendas + 1 WHERE id = ?", [$disputa['valor_vendedor'], $disputa['vendedor_id']]);
            db_update('compras', ['status' => 'concluido', 'confirmado_comprador' => 1, 'confirmado_vendedor' => 1], 'id = ?', [$disputa['compra_id']]);
            create_notification($disputa['vendedor_id'], 'Disputa Resolvida', "Sua disputa foi resolvida a seu favor. Saldo creditado.", 'venda', '/painel/vendas.php');
            create_notification($disputa['comprador_id'], 'Disputa Resolvida', "Disputa resolvida a favor do vendedor.", 'compra', '/painel/compras.php');
        }

        db_insert('admin_log', [
            'admin_id' => $admin_id,
            'acao' => 'resolver_disputa',
            'descricao' => "Disputa #{$id} resolvida - {$resultado}",
            'tipo' => 'disputas',
        ]);

        $success = "Disputa #{$id} resolvida!";
    } else {
        $error = 'Disputa nao encontrada ou resolucao vazia.';
    }
    header('Location: ' . SITE_URL . '/admin/disputas.php');
    exit;
}

// Buscar disputas
$filtro = $_GET['status'] ?? 'aberta';
$page = max(1, intval($_GET['page'] ?? 1));

$where = 'd.status = ?';
$params = [$filtro];
if (!in_array($filtro, ['aberta', 'resolvida'])) { $filtro = 'aberta'; $params = ['aberta']; }

$total = db_count('disputas d', $where, $params);
$pagination = paginate($total, 15, $page);

$disputas = db_fetch_all(
    "SELECT d.*, c.valor, c.anuncio_id, c.metodo_pagamento,
            a.titulo as anuncio_titulo,
            u_comp.nome as comprador_nome, u_vend.nome as vendedor_nome,
            u_abriu.nome as aberto_por_nome
     FROM disputas d
     JOIN compras c ON d.compra_id = c.id
     JOIN anuncios a ON c.anuncio_id = a.id
     JOIN usuarios u_comp ON c.comprador_id = u_comp.id
     JOIN usuarios u_vend ON c.vendedor_id = u_vend.id
     JOIN usuarios u_abriu ON d.aberto_por = u_abriu.id
     WHERE {$where}
     ORDER BY d.criado_em DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

$abertas = db_count('disputas', 'status = ?', ['aberta']);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-gavel" style="color: var(--neon-green);"></i> Gerenciar Disputas
    </h1>

    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>

    <!-- Filtros -->
    <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="?status=aberta" class="btn <?php echo $filtro === 'aberta' ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 0.85rem;">
            Abertas (<?php echo $abertas; ?>)
        </a>
        <a href="?status=resolvida" class="btn <?php echo $filtro === 'resolvida' ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 0.85rem;">
            Resolvidas
        </a>
    </div>

    <?php if (empty($disputas)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--neon-green); margin-bottom: 15px; display: block;"></i>
            <p style="color: var(--text-muted);">Nenhuma disputa <?php echo $filtro; ?>.</p>
        </div>
    <?php else: ?>
        <?php foreach ($disputas as $d): ?>
        <div class="card" style="margin-bottom: 15px; <?php echo $d['status'] === 'aberta' ? 'border-left: 3px solid var(--neon-pink);' : 'border-left: 3px solid var(--neon-green);'; ?>">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                <div>
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 5px;">
                        <strong style="color: var(--text);">Disputa #<?php echo $d['id']; ?></strong>
                        <span style="font-size: 0.8rem; padding: 3px 10px; border-radius: 12px; <?php echo $d['status'] === 'aberta' ? 'background: rgba(255,107,107,0.1); color: var(--neon-pink);' : 'background: rgba(0,255,136,0.1); color: var(--neon-green);'; ?>">
                            <?php echo ucfirst($d['status']); ?>
                        </span>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.85rem;">
                        Compra #<?php echo $d['compra_id']; ?> - <?php echo sanitize($d['anuncio_titulo']); ?> - R$ <?php echo number_format($d['valor'], 2, ',', '.'); ?>
                    </div>
                </div>
                <div style="color: var(--text-muted); font-size: 0.75rem;">
                    Aberta por: <?php echo sanitize($d['aberto_por_nome']); ?> em <?php echo format_date($d['criado_em']); ?>
                </div>
            </div>

            <!-- Partes -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; font-size: 0.85rem;">
                <div style="background: var(--bg-input); padding: 10px; border-radius: 6px;">
                    <span style="color: var(--neon-blue);"><i class="fas fa-user"></i> Comprador:</span>
                    <span style="color: var(--text);"><?php echo sanitize($d['comprador_nome']); ?></span>
                </div>
                <div style="background: var(--bg-input); padding: 10px; border-radius: 6px;">
                    <span style="color: var(--neon-green);"><i class="fas fa-user"></i> Vendedor:</span>
                    <span style="color: var(--text);"><?php echo sanitize($d['vendedor_nome']); ?></span>
                </div>
            </div>

            <!-- Motivo -->
            <div style="background: rgba(255,107,107,0.05); border: 1px solid rgba(255,107,107,0.2); padding: 12px; border-radius: 6px; margin-bottom: 10px;">
                <div style="color: var(--neon-pink); font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">
                    <i class="fas fa-exclamation-triangle"></i> Motivo:
                </div>
                <p style="color: var(--text); margin: 0; font-size: 0.9rem;"><?php echo nl2br(sanitize($d['motivo'])); ?></p>
            </div>

            <?php if ($d['status'] === 'resolvida' && !empty($d['resolucao'])): ?>
            <div style="background: rgba(0,255,136,0.05); border: 1px solid rgba(0,255,136,0.2); padding: 12px; border-radius: 6px;">
                <div style="color: var(--neon-green); font-weight: 600; font-size: 0.85rem; margin-bottom: 5px;">
                    <i class="fas fa-check-circle"></i> Resolucao:
                </div>
                <p style="color: var(--text); margin: 0; font-size: 0.9rem;"><?php echo nl2br(sanitize($d['resolucao'])); ?></p>
            </div>
            <?php endif; ?>

            <!-- Resolver (se aberta) -->
            <?php if ($d['status'] === 'aberta'): ?>
            <div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="acao" value="resolver">
                    <input type="hidden" name="id" value="<?php echo $d['id']; ?>">

                    <div class="form-group">
                        <label class="form-label">Resolucao (obrigatoria) *</label>
                        <textarea name="resolucao" class="form-input" rows="3" required placeholder="Descreva como a disputa sera resolvida..."></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                        <label class="form-label" style="margin: 0;">Resultado:</label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; color: var(--neon-blue);">
                            <input type="radio" name="resultado" value="favor_comprador" checked> Favor Comprador (estorno)
                        </label>
                        <label style="display: flex; align-items: center; gap: 5px; cursor: pointer; color: var(--neon-green);">
                            <input type="radio" name="resultado" value="favor_vendedor"> Favor Vendedor (concluir venda)
                        </label>
                        <button type="submit" class="btn btn-primary" style="margin-left: auto;" onclick="return confirm('Confirmar resolucao desta disputa?')">
                            <i class="fas fa-gavel"></i> Resolver Disputa
                        </button>
                    </div>
                </form>
            </div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if ($pagination['total_pages'] > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php echo render_pagination($pagination, "?status={$filtro}&"); ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>