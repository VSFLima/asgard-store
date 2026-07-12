<?php
/**
 * Asgard Store - Painel: Meus Anúncios
 * Lista de anúncios do usuário com opções de editar/excluir
 */

$page_title = 'Meus Anúncios';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];

// ============================================
// PROCESSAR AÇÕES
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $acao = $_POST['acao'] ?? '';
    $anuncio_id = intval($_POST['anuncio_id'] ?? 0);

    if ($anuncio_id > 0) {
        // Verificar se o anúncio pertence ao usuário
        $anuncio = db_fetch("SELECT * FROM anuncios WHERE id = ? AND usuario_id = ?", [$anuncio_id, $user_id]);

        if ($anuncio) {
            switch ($acao) {
                case 'excluir':
                    // Só pode excluir se estiver pendente ou reprovado
                    if (in_array($anuncio['status'], ['pendente', 'reprovado'])) {
                        db_delete('anuncios', 'id = ?', [$anuncio_id]);
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anúncio excluído com sucesso!'];
                    } else {
                        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Não é possível excluir anúncio com status "' . $anuncio['status'] . '"'];
                    }
                    break;

                case 'pausar':
                    if ($anuncio['status'] === 'aprovado') {
                        db_update('anuncios', ['status' => 'pendente'], 'id = ?', [$anuncio_id]);
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anúncio pausado com sucesso!'];
                    }
                    break;

                case 'reativar':
                    if ($anuncio['status'] === 'pendente') {
                        db_update('anuncios', ['status' => 'aprovado'], 'id = ?', [$anuncio_id]);
                        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anúncio reativado!'];
                    }
                    break;
            }
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Anúncio não encontrado ou não pertence a você.'];
        }
    }

    header('Location: /painel/anuncios.php');
    exit;
}

// ============================================
// PARÂMETROS
// ============================================

$filtro_status = $_GET['status'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// ============================================
// CONSTRUIR QUERY
// ============================================

$where = ["a.usuario_id = ?"];
$params = [$user_id];

if ($filtro_status !== '' && in_array($filtro_status, ['pendente', 'aprovado', 'reprovado', 'vendido'])) {
    $where[] = "a.status = ?";
    $params[] = $filtro_status;
}

$where_sql = implode(' AND ', $where);

// Contar total
$total = db_fetch("SELECT COUNT(*) as total FROM anuncios a WHERE {$where_sql}", $params)['total'];

// Paginação
$pagination = paginate($total, $per_page, $page);

// Buscar anúncios
$anuncios = db_fetch_all("
    SELECT a.*, j.nome as jogo_nome, j.icone as jogo_icone, j.slug as jogo_slug
    FROM anuncios a
    JOIN jogos j ON a.jogo_id = j.id
    WHERE {$where_sql}
    ORDER BY a.criado_em DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

// Estatísticas do usuário
$stats = [
    'total' => db_count('anuncios', "usuario_id = ?", [$user_id]),
    'aprovados' => db_count('anuncios', "usuario_id = ? AND status = 'aprovado'", [$user_id]),
    'pendentes' => db_count('anuncios', "usuario_id = ? AND status = 'pendente'", [$user_id]),
    'reprovados' => db_count('anuncios', "usuario_id = ? AND status = 'reprovado'", [$user_id]),
    'vendidos' => db_count('anuncios', "usuario_id = ? AND status = 'vendido'", [$user_id]),
];

// Verificar se pode criar mais anúncios
$limite_anuncios = 20;
$pode_criar = $stats['total'] < $limite_anuncios;

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="/">Início</a> <i class="fas fa-chevron-right"></i>
        <a href="/painel/">Painel</a> <i class="fas fa-chevron-right"></i>
        <span>Meus Anúncios</span>
    </nav>

    <!-- Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
        <h1 class="section-title">Meus Anúncios</h1>
        <?php if ($pode_criar): ?>
        <a href="/painel/anuncio-novo.php" class="btn btn-green">
            <i class="fas fa-plus"></i> Novo Anúncio
        </a>
        <?php else: ?>
        <span class="btn btn-outline" style="opacity: 0.5; cursor: not-allowed;">
            <i class="fas fa-plus"></i> Limite Atingido
        </span>
        <?php endif; ?>
    </div>

    <!-- Estatísticas -->
    <div class="user-ads-stats">
        <div class="ads-stat-card">
            <i class="fas fa-tags"></i>
            <div>
                <strong><?php echo $stats['total']; ?></strong>
                <span>Total</span>
            </div>
        </div>
        <div class="ads-stat-card active">
            <i class="fas fa-check-circle"></i>
            <div>
                <strong><?php echo $stats['aprovados']; ?></strong>
                <span>Aprovados</span>
            </div>
        </div>
        <div class="ads-stat-card pending">
            <i class="fas fa-clock"></i>
            <div>
                <strong><?php echo $stats['pendentes']; ?></strong>
                <span>Pendentes</span>
            </div>
        </div>
        <div class="ads-stat-card rejected">
            <i class="fas fa-times-circle"></i>
            <div>
                <strong><?php echo $stats['reprovados']; ?></strong>
                <span>Reprovados</span>
            </div>
        </div>
        <div class="ads-stat-card sold">
            <i class="fas fa-shopping-bag"></i>
            <div>
                <strong><?php echo $stats['vendidos']; ?></strong>
                <span>Vendidos</span>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="ads-filters">
        <a href="/painel/anuncios.php" class="ads-filter <?php echo $filtro_status === '' ? 'active' : ''; ?>">
            Todos (<?php echo $stats['total']; ?>)
        </a>
        <a href="/painel/anuncios.php?status=aprovado" class="ads-filter <?php echo $filtro_status === 'aprovado' ? 'active' : ''; ?>">
            ✅ Aprovados (<?php echo $stats['aprovados']; ?>)
        </a>
        <a href="/painel/anuncios.php?status=pendente" class="ads-filter <?php echo $filtro_status === 'pendente' ? 'active' : ''; ?>">
            ⏳ Pendentes (<?php echo $stats['pendentes']; ?>)
        </a>
        <a href="/painel/anuncios.php?status=reprovado" class="ads-filter <?php echo $filtro_status === 'reprovado' ? 'active' : ''; ?>">
            ❌ Reprovados (<?php echo $stats['reprovados']; ?>)
        </a>
        <a href="/painel/anuncios.php?status=vendido" class="ads-filter <?php echo $filtro_status === 'vendido' ? 'active' : ''; ?>">
            💰 Vendidos (<?php echo $stats['vendidos']; ?>)
        </a>
    </div>

    <!-- Lista de Anúncios -->
    <?php if (empty($anuncios)): ?>
    <div class="panel-empty" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 60px 20px; text-align: center;">
        <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.3; display: block; margin-bottom: 15px;"></i>
        <h3 style="color: var(--text-secondary); margin-bottom: 10px;">
            <?php echo $filtro_status ? 'Nenhum anúncio com este status' : 'Você ainda não tem anúncios'; ?>
        </h3>
        <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 0.9rem;">
            <?php echo $filtro_status ? 'Tente filtrar por outro status.' : 'Comece criando seu primeiro anúncio!'; ?>
        </p>
        <?php if (!$filtro_status && $pode_criar): ?>
        <a href="/painel/anuncio-novo.php" class="btn btn-green">
            <i class="fas fa-plus"></i> Criar Primeiro Anúncio
        </a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div class="ads-list">
        <?php foreach ($anuncios as $anuncio): ?>
        <div class="ad-card status-<?php echo $anuncio['status']; ?>">
            <!-- Imagem -->
            <div class="ad-image">
                <?php
                $screenshots = json_decode($anuncio['screenshots'] ?? '[]', true);
                $img = !empty($screenshots[0])
                    ? '/assets/img/uploads/anuncios/' . $screenshots[0]
                    : '/assets/img/games/' . ($anuncio['jogo_icone'] ?? 'default-game.png');
                ?>
                <img src="<?php echo $img; ?>" alt="<?php echo sanitize($anuncio['titulo']); ?>" onerror="this.style.display='none'">
                <span class="ad-status-badge status-<?php echo $anuncio['status']; ?>">
                    <?php
                    $status_labels = [
                        'pendente' => '⏳ Pendente',
                        'aprovado' => '✅ Aprovado',
                        'reprovado' => '❌ Reprovado',
                        'vendido' => '💰 Vendido'
                    ];
                    echo $status_labels[$anuncio['status']] ?? $anuncio['status'];
                    ?>
                </span>
            </div>

            <!-- Info -->
            <div class="ad-info">
                <div class="ad-game">
                    <img src="/assets/img/games/<?php echo sanitize($anuncio['jogo_icone'] ?? 'default-game.png'); ?>" alt="" onerror="this.style.display='none'">
                    <?php echo sanitize($anuncio['jogo_nome']); ?>
                </div>
                <h3 class="ad-title"><?php echo sanitize($anuncio['titulo']); ?></h3>
                <div class="ad-meta">
                    <span><i class="fas fa-eye"></i> <?php echo $anuncio['visualizacoes']; ?></span>
                    <span><i class="fas fa-clock"></i> <?php echo time_ago($anuncio['criado_em']); ?></span>
                    <?php if ($anuncio['nivel_rank']): ?>
                    <span><i class="fas fa-trophy"></i> <?php echo sanitize($anuncio['nivel_rank']); ?></span>
                    <?php endif; ?>
                </div>
                <?php if ($anuncio['status'] === 'reprovado' && !empty($anuncio['motivo_reprovacao'])): ?>
                <div class="ad-rejection-reason">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo sanitize($anuncio['motivo_reprovacao']); ?></span>
                </div>
                <?php endif; ?>
                <div class="ad-price"><?php echo format_money($anuncio['preco']); ?></div>
            </div>

            <!-- Ações -->
            <div class="ad-actions">
                <a href="/loja/anuncio.php?id=<?php echo $anuncio['id']; ?>" class="btn btn-sm btn-outline" title="Ver na loja">
                    <i class="fas fa-eye"></i>
                </a>

                <?php if (in_array($anuncio['status'], ['pendente', 'reprovado'])): ?>
                <a href="/painel/anuncio-editar.php?id=<?php echo $anuncio['id']; ?>" class="btn btn-sm btn-primary" title="Editar">
                    <i class="fas fa-edit"></i>
                </a>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Excluir este anúncio permanentemente?')">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="acao" value="excluir">
                    <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-danger" title="Excluir">
                        <i class="fas fa-trash"></i>
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($anuncio['status'] === 'aprovado'): ?>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Pausar este anúncio?')">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="acao" value="pausar">
                    <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-warning" title="Pausar">
                        <i class="fas fa-pause"></i>
                    </button>
                </form>
                <?php endif; ?>

                <?php if ($anuncio['status'] === 'pendente' && $anuncio['destaque']): ?>
                <form method="POST" style="display: inline;" onsubmit="return confirm('Reativar este anúncio?')">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="acao" value="reativar">
                    <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                    <button type="submit" class="btn btn-sm btn-success" title="Reativar">
                        <i class="fas fa-play"></i>
                    </button>
                </form>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Paginação -->
    <?php
    $base_url = '/painel/anuncios.php';
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
   MY ADS STYLES
   ============================================ */

.user-ads-stats {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.ads-stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.2s;
}

.ads-stat-card:hover {
    border-color: var(--neon-green);
    transform: translateY(-2px);
}

.ads-stat-card i {
    font-size: 1.3rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    color: var(--text-secondary);
    background: rgba(255,255,255,0.05);
}

.ads-stat-card.active i { color: var(--neon-green); background: rgba(0,255,136,0.1); }
.ads-stat-card.pending i { color: var(--neon-yellow); background: rgba(255,200,0,0.1); }
.ads-stat-card.rejected i { color: #ff4444; background: rgba(255,68,68,0.1); }
.ads-stat-card.sold i { color: var(--neon-blue); background: rgba(0,136,255,0.1); }

.ads-stat-card strong {
    display: block;
    font-family: 'Orbitron', monospace;
    font-size: 1.2rem;
    color: var(--text-primary);
}

.ads-stat-card span {
    font-size: 0.8rem;
    color: var(--text-secondary);
}

/* Filters */
.ads-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.ads-filter {
    padding: 8px 16px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 20px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.85rem;
    transition: all 0.2s;
}

.ads-filter:hover {
    border-color: var(--neon-green);
    color: var(--neon-green);
}

.ads-filter.active {
    background: rgba(0,255,136,0.1);
    border-color: var(--neon-green);
    color: var(--neon-green);
    font-weight: 600;
}

/* Ads List */
.ads-list {
    display: flex;
    flex-direction: column;
    gap: 15px;
    margin-bottom: 25px;
}

.ad-card {
    display: flex;
    gap: 20px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 16px;
    transition: all 0.2s;
}

.ad-card:hover {
    border-color: var(--neon-green);
}

.ad-card.status-sold {
    opacity: 0.7;
}

.ad-image {
    position: relative;
    width: 160px;
    height: 100px;
    flex-shrink: 0;
    border-radius: 8px;
    overflow: hidden;
    background: var(--bg-secondary);
}

.ad-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.ad-status-badge {
    position: absolute;
    top: 8px;
    left: 8px;
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 600;
}

.ad-status-badge.status-pendente { background: rgba(255,200,0,0.9); color: #000; }
.ad-status-badge.status-aprovado { background: rgba(0,255,136,0.9); color: #000; }
.ad-status-badge.status-reprovado { background: rgba(255,68,68,0.9); color: #fff; }
.ad-status-badge.status-vendido { background: rgba(0,136,255,0.9); color: #fff; }

.ad-info {
    flex: 1;
    min-width: 0;
    display: flex;
    flex-direction: column;
}

.ad-game {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--neon-green);
    font-size: 0.8rem;
    font-weight: 600;
    margin-bottom: 5px;
}

.ad-game img {
    width: 16px;
    height: 16px;
}

.ad-title {
    font-family: 'Rajdhani', sans-serif;
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 5px;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.ad-meta {
    display: flex;
    gap: 15px;
    color: var(--text-secondary);
    font-size: 0.8rem;
    margin-bottom: 8px;
}

.ad-meta i { margin-right: 4px; }

.ad-rejection-reason {
    display: flex;
    align-items: center;
    gap: 8px;
    background: rgba(255,68,68,0.1);
    border: 1px solid rgba(255,68,68,0.3);
    border-radius: 6px;
    padding: 6px 10px;
    font-size: 0.8rem;
    color: #ff4444;
    margin-bottom: 8px;
}

.ad-price {
    font-family: 'Orbitron', monospace;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--neon-green);
    margin-top: auto;
}

/* Ad Actions */
.ad-actions {
    display: flex;
    flex-direction: column;
    gap: 8px;
    justify-content: center;
    flex-shrink: 0;
}

/* Responsive */
@media (max-width: 768px) {
    .user-ads-stats {
        grid-template-columns: repeat(3, 1fr);
    }

    .ad-card {
        flex-direction: column;
    }

    .ad-image {
        width: 100%;
        height: 150px;
    }

    .ad-actions {
        flex-direction: row;
        justify-content: flex-start;
    }
}

@media (max-width: 480px) {
    .user-ads-stats {
        grid-template-columns: repeat(2, 1fr);
    }

    .ads-filters {
        gap: 6px;
    }

    .ads-filter {
        font-size: 0.75rem;
        padding: 6px 12px;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
