<?php
/**
 * Asgard Store - Loja de Contas
 */

$page_title = 'Loja';
require_once __DIR__ . '/../includes/functions.php';

// Filtros
$jogo_slug = $_GET['jogo'] ?? '';
$rank = $_GET['rank'] ?? '';
$preco_min = floatval($_GET['preco_min'] ?? 0);
$preco_max = floatval($_GET['preco_max'] ?? 99999);
$busca = sanitize($_GET['q'] ?? '');
$ordem = $_GET['ordem'] ?? 'mais_recente';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;

// Buscar jogos para filtro
$jogos = db_fetch_all("SELECT j.*, (SELECT COUNT(*) FROM anuncios a WHERE a.jogo_id = j.id AND a.status = 'aprovado') as total FROM jogos j WHERE j.ativo = 1 ORDER BY j.ordem ASC");

// Construir query
$where = ["a.status = 'aprovado'"];
$params = [];

if (!empty($jogo_slug)) {
    $where[] = "j.slug = ?";
    $params[] = $jogo_slug;
}

if (!empty($busca)) {
    $where[] = "(a.titulo LIKE ? OR a.descricao LIKE ? OR a.nivel_rank LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($preco_min > 0) {
    $where[] = "a.preco >= ?";
    $params[] = $preco_min;
}

if ($preco_max < 99999) {
    $where[] = "a.preco <= ?";
    $params[] = $preco_max;
}

$where_sql = implode(' AND ', $where);

// Contar total
$total = db_fetch(
    "SELECT COUNT(*) as total FROM anuncios a JOIN jogos j ON a.jogo_id = j.id WHERE {$where_sql}",
    $params
)['total'];

// Ordenacao - Prioridade: Admin > Destaque (pago) > Regular
// u.admin = 1 (venda oficial) sempre no topo
// a.destaque = 1 (destaque pago) depois
// resto por ordem selecionada
$order_map = [
    'mais_recente' => 'u.admin DESC, a.destaque DESC, a.criado_em DESC',
    'menor_preco' => 'u.admin DESC, a.destaque DESC, a.preco ASC',
    'maior_preco' => 'u.admin DESC, a.destaque DESC, a.preco DESC',
    'mais_vistos' => 'u.admin DESC, a.destaque DESC, a.visualizacoes DESC'
];
$order_sql = $order_map[$ordem] ?? 'u.admin DESC, a.destaque DESC, a.criado_em DESC';

// Paginacao
$pagination = paginate($total, $per_page, $page);

// Buscar anuncios
$anuncios = db_fetch_all(
    "SELECT a.*, j.nome as jogo_nome, j.icone as jogo_icone, j.slug as jogo_slug,
            u.nome as vendedor_nome, u.nota_media
     FROM anuncios a
     JOIN jogos j ON a.jogo_id = j.id
     JOIN usuarios u ON a.usuario_id = u.id
     WHERE {$where_sql}
     ORDER BY {$order_sql}
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px;">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="/">Inicio</a> <i class="fas fa-chevron-right"></i>
        <span>Loja</span>
        <?php if (!empty($jogo_slug)): ?>
            <i class="fas fa-chevron-right"></i>
            <span><?php echo sanitize($jogos[array_search($jogo_slug, array_column($jogos, 'slug'))]['nome'] ?? ''); ?></span>
        <?php endif; ?>
    </nav>

    <!-- Header -->
    <div class="store-header">
        <h1 class="section-title">
            <?php echo !empty($busca) ? 'Resultados para: ' . sanitize($busca) : 'Loja de Contas'; ?>
        </h1>
        <div class="store-count">
            <?php echo $total; ?> anuncio(s) encontrado(s)
        </div>
    </div>

    <div class="store-layout">
        <!-- Sidebar de Filtros -->
        <aside class="filters-sidebar">
            <form id="filter-form" method="GET" action="/loja/">
                <!-- Busca -->
                <div class="filter-group">
                    <label class="filter-title"><i class="fas fa-search"></i> Buscar</label>
                    <input type="text" name="q" class="form-input" placeholder="Nome, rank..."
                           value="<?php echo sanitize($busca); ?>">
                </div>

                <!-- Jogos -->
                <div class="filter-group">
                    <label class="filter-title"><i class="fas fa-gamepad"></i> Jogos</label>
                    <?php foreach ($jogos as $jogo): ?>
                        <?php if ($jogo['total'] > 0): ?>
                        <div class="filter-option">
                            <input type="checkbox" name="jogo" value="<?php echo $jogo['slug']; ?>"
                                   id="jogo-<?php echo $jogo['slug']; ?>"
                                   <?php echo $jogo_slug === $jogo['slug'] ? 'checked' : ''; ?>
                                   onchange="if(this.checked){window.location='?jogo=<?php echo $jogo['slug']; ?>'}else{window.location='/loja/'}">
                            <label for="jogo-<?php echo $jogo['slug']; ?>">
                                <?php echo sanitize($jogo['nome']); ?>
                                <span class="filter-count">(<?php echo $jogo['total']; ?>)</span>
                            </label>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>

                <!-- Preco -->
                <div class="filter-group">
                    <label class="filter-title"><i class="fas fa-dollar-sign"></i> Faixa de Preco</label>
                    <div class="price-range">
                        <input type="number" name="preco_min" class="price-input" placeholder="Min" step="0.01"
                               value="<?php echo $preco_min > 0 ? $preco_min : ''; ?>" min="0">
                        <span>-</span>
                        <input type="number" name="preco_max" class="price-input" placeholder="Max" step="0.01"
                               value="<?php echo $preco_max < 99999 ? $preco_max : ''; ?>" min="0">
                    </div>
                </div>

                <!-- Botoes -->
                <div class="filter-actions">
                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="/loja/" class="btn btn-outline btn-block" id="clear-filters">
                        <i class="fas fa-times"></i> Limpar
                    </a>
                </div>
            </form>
        </aside>

        <!-- Conteudo Principal -->
        <main class="store-main">
            <!-- Ordenacao -->
            <div class="store-toolbar">
                <div class="store-order">
                    <label>Ordenar por:</label>
                    <select onchange="window.location='?<?php echo http_build_query(array_merge($_GET, ['ordem' => '']); ?>&ordem='+this.value)" class="form-select">
                        <option value="mais_recente" <?php echo $ordem === 'mais_recente' ? 'selected' : ''; ?>>Mais Recente</option>
                        <option value="menor_preco" <?php echo $ordem === 'menor_preco' ? 'selected' : ''; ?>>Menor Preco</option>
                        <option value="maior_preco" <?php echo $ordem === 'maior_preco' ? 'selected' : ''; ?>>Maior Preco</option>
                        <option value="mais_vistos" <?php echo $ordem === 'mais_vistos' ? 'selected' : ''; ?>>Mais Vistos</option>
                    </select>
                </div>
            </div>

            <!-- Grid de Produtos -->
            <div class="products-grid">
                <?php if (empty($anuncios)): ?>
                    <div class="empty-state" style="grid-column: 1/-1;">
                        <div class="empty-state-icon"><i class="fas fa-search"></i></div>
                        <h3 class="empty-state-title">Nenhum anuncio encontrado</h3>
                        <p class="empty-state-desc">Tente ajustar os filtros ou buscar por outros termos.</p>
                        <a href="/loja/" class="btn btn-primary">Limpar Filtros</a>
                    </div>
                <?php else: ?>
                    <?php foreach ($anuncios as $anuncio): ?>
                    <a href="/loja/anuncio.php?id=<?php echo $anuncio['id']; ?>" class="product-card">
                        <div class="product-image">
                            <?php
                            $screenshots = json_decode($anuncio['screenshots'] ?? '[]', true);
                            $img = !empty($screenshots[0])
                                ? '/assets/img/uploads/anuncios/' . $screenshots[0]
                                : '/assets/img/games/' . ($anuncio['jogo_icone'] ?? 'default-game.png');
                            ?>
                            <img src="<?php echo $img; ?>" loading="lazy"
                                 alt="<?php echo sanitize($anuncio['titulo']); ?>"
                                 onerror="this.style.display='none'">
                            <?php if (!empty($anuncio['admin'])): ?>
                                <span class="product-badge badge-admin"><?php echo $anuncio['usuario_id'] == 1 ? '💎' : '💠'; ?> Venda Oficial</span>
                            <?php elseif (!empty($anuncio['destaque'])): ?>
                                <span class="product-badge badge-destaque">⭐ Destaque</span>
                            <?php endif; ?>
                        </div>
                        <div class="product-info">
                            <div class="product-game">
                                <img src="/assets/img/games/<?php echo sanitize($anuncio['jogo_icone'] ?? 'default-game.png'); ?>" 
                                     alt="" onerror="this.style.display='none'">
                                <?php echo sanitize($anuncio['jogo_nome']); ?>
                            </div>
                            <h3 class="product-title"><?php echo sanitize($anuncio['titulo']); ?></h3>
                            <div class="product-meta">
                                <span><i class="fas fa-star" style="color: var(--neon-yellow);"></i> <?php echo number_format($anuncio['nota_media'] ?? 0, 1); ?></span>
                                <span><i class="fas fa-eye"></i> <?php echo $anuncio['visualizacoes']; ?></span>
                            </div>
                            <div class="product-footer">
                                <div class="product-price"><?php echo format_money($anuncio['preco']); ?></div>
                                <?php if (!empty($anuncio['admin'])): ?>
                                    <span class="btn btn-sm btn-green"><?php echo $anuncio['usuario_id'] == 1 ? '💎' : '💠'; ?> Ver</span>
                                <?php else: ?>
                                    <span class="btn btn-sm btn-primary">Ver</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- Paginacao -->
            <?php
            $base_url = '/loja/';
            $query_params = $_GET;
            unset($query_params['page']);
            if (!empty($query_params)) {
                $base_url .= '?' . http_build_query($query_params) . '&';
            } else {
                $base_url .= '?';
            }
            echo render_pagination($pagination, rtrim($base_url, '?&'));
            ?>
        </main>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
