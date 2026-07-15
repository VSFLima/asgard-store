<?php
/**
 * Asgard Store - Painel: Minhas Vendas
 */

$page_title = 'Minhas Vendas';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];

// Processar acoes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $acao = $_POST['acao'] ?? '';
    $compra_id = intval($_POST['compra_id'] ?? 0);

    if ($compra_id > 0) {
        $compra = db_fetch("SELECT * FROM compras WHERE id = ? AND vendedor_id = ?", [$compra_id, $user_id]);

        if ($compra) {
            switch ($acao) {
                case 'confirmar_entrega':
                    if ($compra['status'] === 'pagamento_confirmado') {
                        db_query("UPDATE compras SET status = 'entregando' WHERE id = ?", [$compra_id]);
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Status atualizado para "Entregando"!'];
                    }
                    break;

                case 'confirmar_conclusao':
                    if (in_array($compra['status'], ['entregando', 'entregue'])) {
                        $stmt = db_query(
                            "UPDATE compras SET confirmado_vendedor = 1, status = 'concluido' WHERE id = ? AND status IN ('entregando', 'entregue')",
                            [$compra_id]
                        );
                        if ($stmt->rowCount() > 0) {
                            // Creditar vendedor
                            db_query("UPDATE usuarios SET saldo = saldo + ?, total_vendas = total_vendas + 1 WHERE id = ?", [$compra['valor_vendedor'], $user_id]);
                            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Venda concluida! Valor creditado no seu saldo.'];
                        }
                    }
                    break;
            }
        }
    }

    header('Location: /painel/vendas.php');
    exit;
}

// Filtros
$filtro_status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Query
$where = ["c.vendedor_id = ?"];
$params = [$user_id];

if ($filtro_status !== '' && in_array($filtro_status, ['aguardando_pagamento', 'pagamento_confirmado', 'entregando', 'entregue', 'em_disputa', 'concluido', 'cancelado'])) {
    $where[] = "c.status = ?";
    $params[] = $filtro_status;
}

$where_sql = implode(' AND ', $where);

// Total
$total = db_fetch("SELECT COUNT(*) as total FROM compras c WHERE {$where_sql}", $params)['total'];

// Paginacao
$pagination = paginate($total, $per_page, $page);

// Buscar vendas
$vendas = db_fetch_all("
    SELECT c.*, 
           a.titulo as anuncio_titulo, a.nivel_rank,
           j.nome as jogo_nome, j.icone as jogo_icone,
           u.nome as comprador_nome, u.sobrenome as comprador_sobrenome,
           u.nota_media as comprador_nota
    FROM compras c
    JOIN anuncios a ON c.anuncio_id = a.id
    JOIN jogos j ON a.jogo_id = j.id
    JOIN usuarios u ON c.comprador_id = u.id
    WHERE {$where_sql}
    ORDER BY c.criado_em DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

// Estatisticas
$stats = [
    'total' => db_count('compras', "vendedor_id = ?", [$user_id]),
    'pendentes' => db_count('compras', "vendedor_id = ? AND status = 'pagamento_confirmado'", [$user_id]),
    'em_andamento' => db_count('compras', "vendedor_id = ? AND status IN ('entregando', 'entregue')", [$user_id]),
    'concluidas' => db_count('compras', "vendedor_id = ? AND status = 'concluido'", [$user_id]),
    'disputas' => db_count('compras', "vendedor_id = ? AND status = 'em_disputa'", [$user_id]),
];

$valor_total = db_fetch("SELECT COALESCE(SUM(valor_vendedor), 0) as total FROM compras WHERE vendedor_id = ? AND status IN ('concluido', 'entregue')", [$user_id])['total'];
$valor_saldo = db_fetch("SELECT saldo FROM usuarios WHERE id = ?", [$user_id])['saldo'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="/">Inicio</a> <i class="fas fa-chevron-right"></i>
        <a href="/painel/">Painel</a> <i class="fas fa-chevron-right"></i>
        <span>Minhas Vendas</span>
    </nav>

    <!-- Header -->
    <h1 class="section-title" style="margin-bottom: 30px;">Minhas Vendas</h1>

    <!-- Estatisticas -->
    <div class="user-ads-stats">
        <div class="ads-stat-card">
            <i class="fas fa-shopping-bag"></i>
            <div>
                <strong><?php echo $stats['total']; ?></strong>
                <span>Total</span>
            </div>
        </div>
        <div class="ads-stat-card pending">
            <i class="fas fa-clock"></i>
            <div>
                <strong><?php echo $stats['pendentes']; ?></strong>
                <span>Aguardando</span>
            </div>
        </div>
        <div class="ads-stat-card active">
            <i class="fas fa-truck"></i>
            <div>
                <strong><?php echo $stats['em_andamento']; ?></strong>
                <span>Em Andamento</span>
            </div>
        </div>
        <div class="ads-stat-card sold">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong><?php echo $stats['concluidas']; ?></strong>
                <span>Concluidas</span>
            </div>
        </div>
        <div class="ads-stat-card rejected">
            <i class="fas fa-gavel"></i>
            <div>
                <strong><?php echo $stats['disputas']; ?></strong>
                <span>Disputas</span>
            </div>
        </div>
    </div>

    <!-- Resumo Financeiro -->
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="width: 50px; height: 50px; background: rgba(0,255,136,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-chart-line" style="color: var(--neon-green); font-size: 1.3rem;"></i>
            </div>
            <div>
                <div style="color: var(--text-secondary); font-size: 0.85rem;">Total Ganho</div>
                <div style="font-family: 'Orbitron', monospace; font-size: 1.3rem; font-weight: 700; color: var(--neon-green);">R$ <?php echo number_format($valor_total, 2, ',', '.'); ?></div>
            </div>
        </div>
        <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 20px; display: flex; align-items: center; gap: 15px;">
            <div style="width: 50px; height: 50px; background: rgba(0,212,255,0.1); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-wallet" style="color: var(--neon-blue); font-size: 1.3rem;"></i>
            </div>
            <div>
                <div style="color: var(--text-secondary); font-size: 0.85rem;">Saldo Disponivel</div>
                <div style="font-family: 'Orbitron', monospace; font-size: 1.3rem; font-weight: 700; color: var(--neon-blue);">R$ <?php echo number_format($valor_saldo, 2, ',', '.'); ?></div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="ads-filters">
        <a href="/painel/vendas.php" class="ads-filter <?php echo $filtro_status === '' ? 'active' : ''; ?>">
            Todas (<?php echo $stats['total']; ?>)
        </a>
        <a href="/painel/vendas.php?status=pagamento_confirmado" class="ads-filter <?php echo $filtro_status === 'pagamento_confirmado' ? 'active' : ''; ?>">
            💳 Pagas (<?php echo $stats['pendentes']; ?>)
        </a>
        <a href="/painel/vendas.php?status=entregando" class="ads-filter <?php echo $filtro_status === 'entregando' ? 'active' : ''; ?>">
            🚚 Entregando
        </a>
        <a href="/painel/vendas.php?status=concluido" class="ads-filter <?php echo $filtro_status === 'concluido' ? 'active' : ''; ?>">
            ✅ Concluidas (<?php echo $stats['concluidas']; ?>)
        </a>
        <a href="/painel/vendas.php?status=em_disputa" class="ads-filter <?php echo $filtro_status === 'em_disputa' ? 'active' : ''; ?>">
            ⚠️ Disputas (<?php echo $stats['disputas']; ?>)
        </a>
    </div>

    <!-- Lista de Vendas -->
    <?php if (empty($vendas)): ?>
    <div class="empty-state" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 60px 20px;">
        <div class="empty-state-icon"><i class="fas fa-shopping-bag"></i></div>
        <h3 class="empty-state-title">Nenhuma venda encontrada</h3>
        <p class="empty-state-desc">
            <?php if ($filtro_status): ?>
                Nenhuma venda com este status. <a href="/painel/vendas.php">Ver todas</a>
            <?php else: ?>
                Voce ainda nao realizou nenhuma venda. <a href="/painel/anuncio-novo.php">Crie um anuncio</a>!
            <?php endif; ?>
        </p>
    </div>
    <?php else: ?>
    <div class="compras-list">
        <?php foreach ($vendas as $venda): ?>
        <div class="compra-card status-<?php echo $venda['status']; ?>">
            <!-- Imagem -->
            <div class="compra-image">
                <?php
                $anuncio = db_fetch("SELECT screenshots FROM anuncios WHERE id = ?", [$venda['anuncio_id']]);
                $screenshots = json_decode($anuncio['screenshots'] ?? '[]', true);
                $img = !empty($screenshots[0])
                    ? '/assets/img/uploads/anuncios/' . $screenshots[0]
                    : '/assets/img/games/' . ($venda['jogo_icone'] ?? 'default-game.png');
                ?>
                <img src="<?php echo $img; ?>" alt="<?php echo sanitize($venda['anuncio_titulo']); ?>" onerror="this.style.display='none'">
            </div>

            <!-- Info -->
            <div class="compra-info">
                <div class="compra-game">
                    <img src="/assets/img/games/<?php echo sanitize($venda['jogo_icone'] ?? 'default-game.png'); ?>" alt="" onerror="this.style.display='none'">
                    <?php echo sanitize($venda['jogo_nome']); ?>
                </div>
                <h3 class="compra-title">
                    <a href="/loja/anuncio.php?id=<?php echo $venda['anuncio_id']; ?>"><?php echo sanitize($venda['anuncio_titulo']); ?></a>
                </h3>
                <div class="compra-meta">
                    <span><i class="fas fa-user"></i> <?php echo sanitize($venda['comprador_nome'] . ' ' . $venda['comprador_sobrenome']); ?></span>
                    <span><i class="fas fa-star" style="color: var(--neon-yellow);"></i> <?php echo number_format($venda['comprador_nota'] ?? 0, 1); ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo time_ago($venda['criado_em']); ?></span>
                </div>
                <div class="compra-status-line">
                    <span class="compra-status-badge status-<?php echo $venda['status']; ?>">
                        <?php
                        $status_labels = [
                            'aguardando_pagamento' => '⏳ Aguardando Pagamento',
                            'pagamento_confirmado' => '💳 Pagamento Confirmado',
                            'entregando' => '🚚 Entregando',
                            'entregue' => '📦 Entregue',
                            'em_disputa' => '⚠️ Em Disputa',
                            'concluido' => '✅ Concluido',
                            'cancelado' => '❌ Cancelado'
                        ];
                        echo $status_labels[$venda['status']] ?? $venda['status'];
                        ?>
                    </span>
                </div>
            </div>

            <!-- Valor e Acoes -->
            <div class="compra-right">
                <div class="compra-valor">R$ <?php echo number_format($venda['valor_vendedor'], 2, ',', '.'); ?></div>
                <div class="compra-pagamento">
                    <?php echo $venda['metodo_pagamento'] === 'pix' ? ' PIX' : ' Crypto'; ?>
                </div>

                <!-- Acoes -->
                <div class="compra-actions">
                    <?php if ($venda['status'] === 'pagamento_confirmado'): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmar que esta entregando a conta?')">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="acao" value="confirmar_entrega">
                        <input type="hidden" name="compra_id" value="<?php echo $venda['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-primary" title="Confirmar Entrega">
                            <i class="fas fa-truck"></i> Entregar
                        </button>
                    </form>
                    <?php endif; ?>

                    <?php if (in_array($venda['status'], ['entregando', 'entregue'])): ?>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Confirmar conclusao da venda? O valor sera creditado no seu saldo.')">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="acao" value="confirmar_conclusao">
                        <input type="hidden" name="compra_id" value="<?php echo $venda['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-success" title="Confirmar Conclusao">
                            <i class="fas fa-check"></i> Concluir
                        </button>
                    </form>
                    <?php endif; ?>

                    <a href="/loja/anuncio.php?id=<?php echo $venda['anuncio_id']; ?>" class="btn btn-sm btn-outline" title="Ver Anuncio">
                        <i class="fas fa-eye"></i>
                    </a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Paginacao -->
    <?php
    $base_url = '/painel/vendas.php';
    $query_params = $_GET;
    unset($query_params['page']);
    if (!empty($query_params)) {
        $base_url .= '?' . http_build_query($query_params) . '&';
    } else {
        $base_url .= '?';
    }
    echo render_pagination($pagination, rtrim($base_url, '?&'));
    ?>
    <?php endif; ?>
</div>

<style>
/* ============================================
   SALES PAGE STYLES
   ============================================ */

.compras-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 25px;
}

.compra-card {
    display: flex;
    gap: 20px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px;
    transition: all 0.2s;
}

.compra-card:hover {
    border-color: var(--neon-green);
}

.compra-card.status-concluido {
    opacity: 0.85;
}

.compra-card.status-cancelado {
    opacity: 0.5;
}

/* Compra Image */
.compra-image {
    width: 120px;
    height: 90px;
    flex-shrink: 0;
    border-radius: 8px;
    overflow: hidden;
    background: var(--bg-secondary);
}

.compra-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

/* Compra Info */
.compra-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.compra-game {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--neon-green);
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.compra-game img {
    width: 16px;
    height: 16px;
}

.compra-title {
    font-family: 'Rajdhani', sans-serif;
    font-size: 1.05rem;
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.compra-title a {
    color: inherit;
    text-decoration: none;
}

.compra-title a:hover {
    color: var(--neon-green);
}

.compra-meta {
    display: flex;
    gap: 15px;
    color: var(--text-secondary);
    font-size: 0.8rem;
    margin-bottom: 8px;
}

.compra-meta i { margin-right: 4px; }

.compra-status-line {
    margin-top: auto;
}

.compra-status-badge {
    display: inline-block;
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
}

.compra-status-badge.status-aguardando_pagamento { background: rgba(255,200,0,0.15); color: var(--neon-yellow); }
.compra-status-badge.status-pagamento_confirmado { background: rgba(0,136,255,0.15); color: var(--neon-blue); }
.compra-status-badge.status-entregando { background: rgba(0,255,136,0.15); color: var(--neon-green); }
.compra-status-badge.status-entregue { background: rgba(0,255,136,0.2); color: var(--neon-green); }
.compra-status-badge.status-em_disputa { background: rgba(255,68,68,0.15); color: #ff4444; }
.compra-status-badge.status-concluido { background: rgba(0,255,136,0.25); color: var(--neon-green); }
.compra-status-badge.status-cancelado { background: rgba(255,68,68,0.1); color: #ff4444; }

/* Compra Right */
.compra-right {
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    justify-content: space-between;
    flex-shrink: 0;
    min-width: 140px;
}

.compra-valor {
    font-family: 'Orbitron', monospace;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--neon-green);
}

.compra-pagamento {
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-bottom: 10px;
}

.compra-actions {
    display: flex;
    gap: 6px;
}

/* Responsive */
@media (max-width: 768px) {
    .compra-card {
        flex-direction: column;
    }

    .compra-image {
        width: 100%;
        height: 150px;
    }

    .compra-right {
        flex-direction: row;
        justify-content: space-between;
        align-items: center;
        min-width: auto;
    }

    .compra-actions {
        justify-content: flex-end;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
