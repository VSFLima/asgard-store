<?php
/**
 * Asgard Store - Admin: Gerenciar Destaques Premium
 */

$page_title = 'Destaques Premium';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

// Verificar expiracoes automaticamente
$expirados = db_query(
    "UPDATE destaques_premium SET status = 'expirado' WHERE status = 'ativo' AND data_fim < NOW()"
);
if ($expirados->rowCount() > 0) {
    // Desativar destaque nos anuncios expirados
    db_query(
        "UPDATE anuncios a JOIN destaques_premium dp ON a.id = dp.anuncio_id 
         SET a.destaque = 0 WHERE dp.status = 'expirado' AND a.destaque = 1"
    );
}

// Cancelar destaque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'cancelar') {
    require_csrf();
    $id = intval($_POST['id'] ?? 0);
    $destaque = db_fetch("SELECT * FROM destaques_premium WHERE id = ? AND status = 'ativo'", [$id]);
    if ($destaque) {
        db_update('destaques_premium', ['status' => 'cancelado'], 'id = ?', [$id]);
        db_update('anuncios', ['destaque' => 0], 'id = ?', [$destaque['anuncio_id']]);
        db_insert('admin_log', [
            'admin_id' => $_SESSION['user_id'],
            'acao' => 'cancelar_destaque',
            'descricao' => "Destaque premium #{$id} cancelado",
            'tipo' => 'destaques',
        ]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Destaque cancelado!'];
    }
    header('Location: ' . SITE_URL . '/admin/destaques.php');
    exit;
}

// Filtros
$filtro = $_GET['status'] ?? 'ativo';
$page = max(1, intval($_GET['page'] ?? 1));

$where = 'dp.status = ?';
$params = [$filtro];
if (!in_array($filtro, ['ativo', 'expirado', 'cancelado'])) { $filtro = 'ativo'; $params = ['ativo']; }

$total = db_count('destaques_premium dp', $where, $params);
$pagination = paginate($total, 20, $page);

$destaques = db_fetch_all(
    "SELECT dp.*, a.titulo as anuncio_titulo, a.preco, a.status as anuncio_status,
            u.nome as usuario_nome, j.nome as jogo_nome
     FROM destaques_premium dp
     JOIN anuncios a ON dp.anuncio_id = a.id
     JOIN usuarios u ON dp.usuario_id = u.id
     JOIN jogos j ON a.jogo_id = j.id
     WHERE {$where}
     ORDER BY dp.criado_em DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

// Stats
$ativos = db_count('destaques_premium', "status = 'ativo'");
$valor_total = db_fetch("SELECT COALESCE(SUM(valor_pago), 0) as total FROM destaques_premium WHERE status IN ('ativo', 'expirado')")['total'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-star" style="color: var(--neon-yellow);"></i> Destaques Premium
    </h1>

    <?php if ($success): ?><div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div><?php endif; ?>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <div class="card" style="text-align: center; padding: 15px;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neon-yellow);"><?php echo $ativos; ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">Ativos</div>
        </div>
        <div class="card" style="text-align: center; padding: 15px;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neon-green);">R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">Faturamento</div>
        </div>
    </div>

    <!-- Filtros -->
    <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
        <?php foreach (['ativo', 'expirado', 'cancelado'] as $f): ?>
            <a href="?status=<?php echo $f; ?>" class="btn <?php echo $filtro === $f ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 0.85rem;">
                <?php echo ucfirst($f); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Lista -->
    <?php if (empty($destaques)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <i class="fas fa-star" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px; display: block;"></i>
            <p style="color: var(--text-muted);">Nenhum destaque <?php echo $filtro; ?>.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x: auto;">
            <div class="card">
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="border-bottom: 2px solid var(--border-color);">
                            <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Anuncio</th>
                            <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Vendedor</th>
                            <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Jogo</th>
                            <th style="text-align: center; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Duracao</th>
                            <th style="text-align: right; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Pago</th>
                            <th style="text-align: center; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Expira</th>
                            <th style="text-align: center; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Status</th>
                            <th style="text-align: right; padding: 10px; color: var(--text-muted); font-size: 0.8rem;">Acoes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($destaques as $d): ?>
                        <?php
                        $expira = strtotime($d['data_fim']);
                        $dias_restantes = max(0, floor(($expira - time()) / 86400));
                        $status_style = match($d['status']) {
                            'ativo' => 'background: rgba(0,255,136,0.1); color: var(--neon-green);',
                            'expirado' => 'background: rgba(255,193,7,0.1); color: var(--neon-yellow);',
                            'cancelado' => 'background: rgba(255,107,107,0.1); color: var(--neon-pink);',
                            default => '',
                        };
                        ?>
                        <tr style="border-bottom: 1px solid var(--border-color);">
                            <td style="padding: 10px;">
                                <a href="/loja/anuncio.php?id=<?php echo $d['anuncio_id']; ?>" target="_blank" style="color: var(--text); text-decoration: none; font-weight: 600;">
                                    <?php echo sanitize($d['anuncio_titulo']); ?>
                                </a>
                                <div style="color: var(--text-muted); font-size: 0.75rem;">R$ <?php echo number_format($d['preco'], 2, ',', '.'); ?></div>
                            </td>
                            <td style="padding: 10px; color: var(--text-muted); font-size: 0.85rem;"><?php echo sanitize($d['usuario_nome']); ?></td>
                            <td style="padding: 10px;">
                                <span style="background: rgba(0,255,136,0.1); color: var(--neon-green); padding: 2px 8px; border-radius: 4px; font-size: 0.8rem;">
                                    <?php echo sanitize($d['jogo_nome']); ?>
                                </span>
                            </td>
                            <td style="padding: 10px; text-align: center; color: var(--text-muted); font-size: 0.85rem;">
                                <?php echo $d['duracao_dias']; ?> dias
                            </td>
                            <td style="padding: 10px; text-align: right; color: var(--neon-green); font-weight: 600;">
                                R$ <?php echo number_format($d['valor_pago'], 2, ',', '.'); ?>
                            </td>
                            <td style="padding: 10px; text-align: center; font-size: 0.8rem;">
                                <?php if ($d['status'] === 'ativo'): ?>
                                    <span style="color: <?php echo $dias_restantes <= 2 ? 'var(--neon-pink)' : 'var(--text-muted)'; ?>;">
                                        <?php echo $dias_restantes; ?> dia(s)
                                    </span>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="padding: 10px; text-align: center;">
                                <span style="<?php echo $status_style; ?> padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                                    <?php echo ucfirst($d['status']); ?>
                                </span>
                            </td>
                            <td style="padding: 10px; text-align: right;">
                                <?php if ($d['status'] === 'ativo'): ?>
                                <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirm('Cancelar este destaque?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="acao" value="cancelar">
                                    <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                    <button type="submit" style="background: none; border: none; color: var(--neon-pink); cursor: pointer; padding: 5px;" title="Cancelar">
                                        <i class="fas fa-times-circle"></i>
                                    </button>
                                </form>
                                <?php else: ?>
                                    <span style="color: var(--text-muted);">-</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php echo render_pagination($pagination, "?status={$filtro}&"); ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>