<?php
/**
 * Asgard Store - Admin: Gerenciar Anuncios
 */

$page_title = 'Gerenciar Anuncios';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// Processar acoes
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $acao = $_POST['acao'] ?? '';
    $anuncio_id = intval($_POST['anuncio_id'] ?? 0);

    if ($anuncio_id > 0) {
        $anuncio = db_fetch("SELECT a.*, u.nome, u.sobrenome FROM anuncios a JOIN usuarios u ON a.usuario_id = u.id WHERE a.id = ?", [$anuncio_id]);

        if ($anuncio) {
            switch ($acao) {
                case 'aprovar':
                    db_query("UPDATE anuncios SET status = 'aprovado', motivo_reprovacao = NULL WHERE id = ?", [$anuncio_id]);
                    db_insert('admin_log', [
                        'admin_id' => $_SESSION['user_id'],
                        'acao' => 'Aprovar anuncio',
                        'descricao' => 'Anuncio: ' . $anuncio['titulo'] . ' | Vendedor: ' . $anuncio['nome'] . ' ' . $anuncio['sobrenome'],
                        'tipo' => 'aprovacao'
                    ]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anuncio aprovado com sucesso!'];
                    break;

                case 'reprovar':
                    $motivo = trim($_POST['motivo'] ?? '');
                    if (empty($motivo)) {
                        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Informe o motivo da reprovacao.'];
                        header('Location: /admin/anuncios.php');
                        exit;
                    }
                    db_query("UPDATE anuncios SET status = 'reprovado', motivo_reprovacao = ? WHERE id = ?", [$motivo, $anuncio_id]);
                    db_insert('admin_log', [
                        'admin_id' => $_SESSION['user_id'],
                        'acao' => 'Reprovar anuncio',
                        'descricao' => 'Anuncio: ' . $anuncio['titulo'] . ' | Vendedor: ' . $anuncio['nome'] . ' ' . $anuncio['sobrenome'] . ' | Motivo: ' . $motivo,
                        'tipo' => 'reprovacao'
                    ]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anuncio reprovado.'];
                    break;

                case 'excluir':
                    db_query("DELETE FROM anuncios WHERE id = ?", [$anuncio_id]);
                    db_insert('admin_log', [
                        'admin_id' => $_SESSION['user_id'],
                        'acao' => 'Excluir anuncio',
                        'descricao' => 'Anuncio: ' . $anuncio['titulo'] . ' | Vendedor: ' . $anuncio['nome'] . ' ' . $anuncio['sobrenome'],
                        'tipo' => 'outro'
                    ]);
                    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anuncio excluido com sucesso!'];
                    break;
            }
        }
    }

    header('Location: /admin/anuncios.php' . (isset($_GET['status']) ? '?status=' . urlencode($_GET['status']) : ''));
    exit;
}

// Filtros
$filtro_status = $_GET['status'] ?? 'pendente';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;

// Query
$where = [];
$params = [];

if ($filtro_status !== '' && in_array($filtro_status, ['pendente', 'aprovado', 'reprovado', 'vendido'])) {
    $where[] = "a.status = ?";
    $params[] = $filtro_status;
}

if (!empty($where)) {
    $where_sql = implode(' AND ', $where);
} else {
    $where_sql = '1=1';
}

// Total
$total = db_fetch("SELECT COUNT(*) as total FROM anuncios a WHERE {$where_sql}", $params)['total'];

// Paginacao
$pagination = paginate($total, $per_page, $page);

// Buscar anuncios
$anuncios = db_fetch_all("
    SELECT a.*, j.nome as jogo_nome, j.icone as jogo_icone, j.slug as jogo_slug,
           u.nome as vendedor_nome, u.sobrenome as vendedor_sobrenome, u.email as vendedor_email,
           u.nota_media as vendedor_nota, u.total_vendas as vendedor_vendas
    FROM anuncios a
    JOIN jogos j ON a.jogo_id = j.id
    JOIN usuarios u ON a.usuario_id = u.id
    WHERE {$where_sql}
    ORDER BY a.criado_em ASC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

// Estatisticas
$stats = [
    'total' => db_count('anuncios'),
    'pendentes' => db_count('anuncios', "status = 'pendente'"),
    'aprovados' => db_count('anuncios', "status = 'aprovado'"),
    'reprovados' => db_count('anuncios', "status = 'reprovado'"),
    'vendidos' => db_count('anuncios', "status = 'vendido'"),
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <i class="fas fa-shield-halved"></i>
            <span>Admin Panel</span>
        </div>
        <nav class="admin-nav">
            <a href="/admin/" class="admin-nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="/admin/usuarios.php" class="admin-nav-item"><i class="fas fa-users"></i> Usuarios</a>
            <a href="/admin/anuncios.php" class="admin-nav-item active"><i class="fas fa-tags"></i> Anuncios <?php if ($stats['pendentes'] > 0): ?><span class="admin-badge"><?php echo $stats['pendentes']; ?></span><?php endif; ?></a>
            <a href="/admin/compras.php" class="admin-nav-item"><i class="fas fa-shopping-cart"></i> Compras</a>
            <a href="/admin/jogos.php" class="admin-nav-item"><i class="fas fa-gamepad"></i> Jogos</a>
            <a href="/admin/creditos.php" class="admin-nav-item"><i class="fas fa-coins"></i> Creditos</a>
            <a href="/admin/saques.php" class="admin-nav-item"><i class="fas fa-money-bill-wave"></i> Saques</a>
            <a href="/admin/redes_sociais.php" class="admin-nav-item"><i class="fas fa-share-nodes"></i> Redes Sociais</a>
            <a href="/admin/config.php" class="admin-nav-item"><i class="fas fa-gear"></i> Configuracoes</a>
        </nav>
    </aside>

    <main class="admin-main">
        <!-- Header -->
        <div class="admin-header">
            <div>
                <h1>Gerenciar Anuncios</h1>
                <p>Aprove, reprove ou exclua anuncios da plataforma.</p>
            </div>
        </div>

        <!-- Estatisticas -->
        <div class="admin-metrics secondary" style="margin-bottom: 25px;">
            <div class="metric-card-sm">
                <i class="fas fa-tags"></i>
                <div>
                    <strong><?php echo $stats['total']; ?></strong>
                    <span>Total</span>
                </div>
            </div>
            <div class="metric-card-sm warning">
                <i class="fas fa-clock"></i>
                <div>
                    <strong><?php echo $stats['pendentes']; ?></strong>
                    <span>Pendentes</span>
                </div>
            </div>
            <div class="metric-card-sm">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong><?php echo $stats['aprovados']; ?></strong>
                    <span>Aprovados</span>
                </div>
            </div>
            <div class="metric-card-sm">
                <i class="fas fa-times-circle"></i>
                <div>
                    <strong><?php echo $stats['reprovados']; ?></strong>
                    <span>Reprovados</span>
                </div>
            </div>
            <div class="metric-card-sm">
                <i class="fas fa-shopping-bag"></i>
                <div>
                    <strong><?php echo $stats['vendidos']; ?></strong>
                    <span>Vendidos</span>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="ads-filters" style="margin-bottom: 25px;">
            <a href="/admin/anuncios.php?status=pendente" class="ads-filter <?php echo $filtro_status === 'pendente' ? 'active' : ''; ?>">
                ⏳ Pendentes (<?php echo $stats['pendentes']; ?>)
            </a>
            <a href="/admin/anuncios.php?status=aprovado" class="ads-filter <?php echo $filtro_status === 'aprovado' ? 'active' : ''; ?>">
                ✅ Aprovados (<?php echo $stats['aprovados']; ?>)
            </a>
            <a href="/admin/anuncios.php?status=reprovado" class="ads-filter <?php echo $filtro_status === 'reprovado' ? 'active' : ''; ?>">
                ❌ Reprovados (<?php echo $stats['reprovados']; ?>)
            </a>
            <a href="/admin/anuncios.php?status=vendido" class="ads-filter <?php echo $filtro_status === 'vendido' ? 'active' : ''; ?>">
                💰 Vendidos (<?php echo $stats['vendidos']; ?>)
            </a>
            <a href="/admin/anuncios.php" class="ads-filter <?php echo $filtro_status === '' ? 'active' : ''; ?>">
                📋 Todos (<?php echo $stats['total']; ?>)
            </a>
        </div>

        <!-- Lista de Anuncios -->
        <?php if (empty($anuncios)): ?>
        <div class="panel-empty" style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 12px; padding: 60px 20px; text-align: center;">
            <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-secondary); opacity: 0.3; display: block; margin-bottom: 15px;"></i>
            <h3 style="color: var(--text-secondary);">Nenhum anuncio encontrado</h3>
        </div>
        <?php else: ?>
        <div class="admin-ads-list">
            <?php foreach ($anuncios as $anuncio): ?>
            <div class="admin-ad-card status-<?php echo $anuncio['status']; ?>">
                <!-- Preview da Imagem -->
                <div class="admin-ad-preview">
                    <?php
                    $screenshots = json_decode($anuncio['screenshots'] ?? '[]', true);
                    $img = !empty($screenshots[0])
                        ? '/assets/img/uploads/anuncios/' . $screenshots[0]
                        : '/assets/img/games/' . ($anuncio['jogo_icone'] ?? 'default-game.png');
                    ?>
                    <img src="<?php echo $img; ?>" alt="<?php echo sanitize($anuncio['titulo']); ?>" onerror="this.style.display='none'">
                    <span class="admin-ad-status status-<?php echo $anuncio['status']; ?>">
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

                <!-- Info do Anuncio -->
                <div class="admin-ad-info">
                    <div class="admin-ad-header">
                        <div class="admin-ad-game">
                            <img src="/assets/img/games/<?php echo sanitize($anuncio['jogo_icone'] ?? 'default-game.png'); ?>" alt="" onerror="this.style.display='none'">
                            <?php echo sanitize($anuncio['jogo_nome']); ?>
                        </div>
                        <div class="admin-ad-price"><?php echo format_money($anuncio['preco']); ?></div>
                    </div>
                    <h3 class="admin-ad-title">
                        <a href="/loja/anuncio.php?id=<?php echo $anuncio['id']; ?>" target="_blank"><?php echo sanitize($anuncio['titulo']); ?></a>
                    </h3>
                    <?php if (!empty($anuncio['descricao'])): ?>
                    <p class="admin-ad-desc"><?php echo sanitize(substr($anuncio['descricao'], 0, 200)); ?><?php echo strlen($anuncio['descricao']) > 200 ? '...' : ''; ?></p>
                    <?php endif; ?>
                    <div class="admin-ad-meta">
                        <span><i class="fas fa-user"></i> <?php echo sanitize($anuncio['vendedor_nome'] . ' ' . $anuncio['vendedor_sobrenome']); ?></span>
                        <span><i class="fas fa-envelope"></i> <?php echo sanitize($anuncio['vendedor_email']); ?></span>
                        <span><i class="fas fa-eye"></i> <?php echo $anuncio['visualizacoes']; ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo time_ago($anuncio['criado_em']); ?></span>
                    </div>
                    <?php if ($anuncio['nivel_rank']): ?>
                    <div class="admin-ad-specs">
                        <span><strong>Rank/Nivel:</strong> <?php echo sanitize($anuncio['nivel_rank']); ?></span>
                        <?php if ($anuncio['servidor']): ?>
                        <span><strong>Servidor:</strong> <?php echo sanitize($anuncio['servidor']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php if ($anuncio['status'] === 'reprovado' && !empty($anuncio['motivo_reprovacao'])): ?>
                    <div class="admin-ad-rejection">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Motivo da Reprovacao:</strong> <?php echo sanitize($anuncio['motivo_reprovacao']); ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Acoes -->
                <?php if ($anuncio['status'] === 'pendente'): ?>
                <div class="admin-ad-actions">
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Aprovar este anuncio?')">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="acao" value="aprovar">
                        <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-check"></i> Aprovar
                        </button>
                    </form>
                    <button class="btn btn-danger btn-block" onclick="openReprovar(<?php echo $anuncio['id']; ?>)">
                        <i class="fas fa-times"></i> Reprovar
                    </button>
                </div>
                <?php else: ?>
                <div class="admin-ad-actions">
                    <a href="/loja/anuncio.php?id=<?php echo $anuncio['id']; ?>" class="btn btn-outline btn-block" target="_blank">
                        <i class="fas fa-eye"></i> Ver
                    </a>
                    <form method="POST" style="display: inline;" onsubmit="return confirm('Excluir este anuncio permanentemente?')">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="acao" value="excluir">
                        <input type="hidden" name="anuncio_id" value="<?php echo $anuncio['id']; ?>">
                        <button type="submit" class="btn btn-danger btn-block">
                            <i class="fas fa-trash"></i> Excluir
                        </button>
                    </form>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginacao -->
        <?php
        $base_url = '/admin/anuncios.php';
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
    </main>
</div>

<!-- Modal Reprovar -->
<div id="reprovar-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.8); z-index: 10000; align-items: center; justify-content: center;">
    <div style="background: var(--bg-card); border: 1px solid var(--border-color); border-radius: 16px; padding: 30px; max-width: 500px; width: 90%;">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-times-circle" style="color: #ff4444; margin-right: 8px;"></i> Reprovar Anuncio</h3>
        <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 0.9rem;">
            Informe o motivo da reprovacao. O vendedor sera notificado.
        </p>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="acao" value="reprovar">
            <input type="hidden" name="anuncio_id" id="reprovar-anuncio-id">
            <div class="form-group">
                <label class="form-label">Motivo da Reprovacao *</label>
                <textarea name="motivo" class="form-textarea" rows="4" required placeholder="Descreva o motivo..."></textarea>
            </div>
            <div style="display: flex; gap: 10px; justify-content: flex-end;">
                <button type="button" class="btn btn-outline" onclick="closeReprovar()">Cancelar</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-times"></i> Reprovar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openReprovar(anuncioId) {
    document.getElementById('reprovar-anuncio-id').value = anuncioId;
    var modal = document.getElementById('reprovar-modal');
    modal.style.display = 'flex';
}

function closeReprovar() {
    var modal = document.getElementById('reprovar-modal');
    modal.style.display = 'none';
}

document.getElementById('reprovar-modal').addEventListener('click', function(e) {
    if (e.target === this) closeReprovar();
});
</script>

<style>
/* ============================================
   ADMIN ADS MANAGEMENT STYLES
   ============================================ */

/* Filters */
.ads-filters {
    display: flex;
    gap: 10px;
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

/* Admin Ads List */
.admin-ads-list {
    display: flex;
    flex-direction: column;
    gap: 20px;
    margin-bottom: 25px;
}

.admin-ad-card {
    display: flex;
    gap: 20px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 20px;
    transition: all 0.2s;
}

.admin-ad-card:hover {
    border-color: var(--neon-green);
}

.admin-ad-card.status-pendente {
    border-left: 4px solid var(--neon-yellow);
}

.admin-ad-card.status-reprovado {
    border-left: 4px solid #ff4444;
    opacity: 0.7;
}

/* Preview */
.admin-ad-preview {
    position: relative;
    width: 180px;
    height: 130px;
    flex-shrink: 0;
    border-radius: 8px;
    overflow: hidden;
    background: var(--bg-secondary);
}

.admin-ad-preview img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.admin-ad-status {
    position: absolute;
    top: 8px;
    left: 8px;
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 600;
}

.admin-ad-status.status-pendente { background: rgba(255,200,0,0.9); color: #000; }
.admin-ad-status.status-aprovado { background: rgba(0,255,136,0.9); color: #000; }
.admin-ad-status.status-reprovado { background: rgba(255,68,68,0.9); color: #fff; }
.admin-ad-status.status-vendido { background: rgba(0,136,255,0.9); color: #fff; }

/* Info */
.admin-ad-info {
    flex: 1;
    min-width: 0;
}

.admin-ad-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 8px;
}

.admin-ad-game {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    color: var(--neon-green);
    font-size: 0.8rem;
    font-weight: 600;
}

.admin-ad-game img {
    width: 16px;
    height: 16px;
}

.admin-ad-price {
    font-family: 'Orbitron', monospace;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--neon-green);
}

.admin-ad-title {
    font-family: 'Rajdhani', sans-serif;
    font-size: 1.2rem;
    font-weight: 600;
    margin-bottom: 8px;
}

.admin-ad-title a {
    color: var(--text-primary);
    text-decoration: none;
}

.admin-ad-title a:hover {
    color: var(--neon-green);
}

.admin-ad-desc {
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 10px;
    line-height: 1.5;
}

.admin-ad-meta {
    display: flex;
    gap: 20px;
    color: var(--text-secondary);
    font-size: 0.8rem;
    margin-bottom: 8px;
}

.admin-ad-meta i { margin-right: 4px; }

.admin-ad-specs {
    display: flex;
    gap: 20px;
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-bottom: 8px;
}

.admin-ad-rejection {
    background: rgba(255,68,68,0.1);
    border: 1px solid rgba(255,68,68,0.3);
    border-radius: 8px;
    padding: 10px 15px;
    font-size: 0.85rem;
    color: #ff4444;
    margin-top: 10px;
}

/* Actions */
.admin-ad-actions {
    display: flex;
    flex-direction: column;
    gap: 10px;
    justify-content: center;
    flex-shrink: 0;
    min-width: 140px;
}

.btn-block {
    display: block;
    width: 100%;
    text-align: center;
}

/* Responsive */
@media (max-width: 768px) {
    .admin-ad-card {
        flex-direction: column;
    }

    .admin-ad-preview {
        width: 100%;
        height: 180px;
    }

    .admin-ad-actions {
        flex-direction: row;
        justify-content: flex-start;
    }

    .btn-block {
        width: auto;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
