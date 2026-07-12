<?php
/**
 * Asgard Store - Detalhes do Anuncio
 */

require_once __DIR__ . '/../includes/functions.php';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: /loja/');
    exit;
}

// Buscar anuncio
$anuncio = db_fetch(
    "SELECT a.*, j.nome as jogo_nome, j.slug as jogo_slug, j.icone as jogo_icone, j.moeda_nome,
            u.id as vendedor_id, u.nome as vendedor_nome, u.sobrenome as vendedor_sobrenome,
            u.nota_media as vendedor_nota, u.total_vendas as vendedor_vendas,
            u.avatar as vendedor_avatar, u.criado_em as vendedor_desde
     FROM anuncios a
     JOIN jogos j ON a.jogo_id = j.id
     JOIN usuarios u ON a.usuario_id = u.id
     WHERE a.id = ? AND a.status = 'aprovado'",
    [$id]
);

if (!$anuncio) {
    header('Location: /loja/');
    exit;
}

// Incrementar visualizacoes
// Evitar contar visualizacoes duplicadas (1 por sessao)
$viewed_key = 'viewed_anuncio_' . $id;
if (!isset($_SESSION[$viewed_key])) {
    db_query("UPDATE anuncios SET visualizacoes = visualizacoes + 1 WHERE id = ?", [$id]);
    $_SESSION[$viewed_key] = true;
}

$page_title = $anuncio['titulo'];

// Verificar se e favorito
$is_favorite = false;
if (is_logged_in()) {
    $fav = db_fetch("SELECT id FROM favoritos WHERE usuario_id = ? AND anuncio_id = ?", [$_SESSION['user_id'], $id]);
    $is_favorite = !empty($fav);
}

// Anuncios similares
$similares = db_fetch_all(
    "SELECT a.*, j.nome as jogo_nome, j.icone as jogo_icone
     FROM anuncios a
     JOIN jogos j ON a.jogo_id = j.id
     WHERE a.status = 'aprovado' AND a.jogo_id = ? AND a.id != ?
     ORDER BY a.criado_em DESC
     LIMIT 4",
    [$anuncio['jogo_id'], $id]
);

// Screenshots
$screenshots = json_decode($anuncio['screenshots'] ?? '[]', true);
if (empty($screenshots)) {
    $screenshots = [];
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="/">Inicio</a> <i class="fas fa-chevron-right"></i>
        <a href="/loja/">Loja</a> <i class="fas fa-chevron-right"></i>
        <a href="/loja/?jogo=<?php echo $anuncio['jogo_slug']; ?>"><?php echo sanitize($anuncio['jogo_nome']); ?></a> <i class="fas fa-chevron-right"></i>
        <span><?php echo sanitize($anuncio['titulo']); ?></span>
    </nav>

    <div class="product-detail">
        <!-- Coluna da Esquerda - Galeria -->
        <div class="product-gallery">
            <div class="gallery-main">
                <?php if (!empty($screenshots)): ?>
                    <img src="/assets/img/uploads/anuncios/<?php echo sanitize($screenshots[0]); ?>" 
                         alt="<?php echo sanitize($anuncio['titulo']); ?>"
                         id="main-image">
                <?php else: ?>
                    <div class="gallery-placeholder">
                        <i class="fas fa-image"></i>
                        <span>Sem imagem</span>
                    </div>
                <?php endif; ?>
            </div>
            <?php if (count($screenshots) > 1): ?>
            <div class="gallery-thumbs">
                <?php foreach ($screenshots as $i => $img): ?>
                <div class="gallery-thumb <?php echo $i === 0 ? 'active' : ''; ?>" onclick="changeImage(this, '/assets/img/uploads/anuncios/<?php echo sanitize($img); ?>')">
                    <img src="/assets/img/uploads/anuncios/<?php echo sanitize($img); ?>" alt="Screenshot <?php echo $i + 1; ?>">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Coluna da Direita - Info -->
        <div class="product-detail-info">
            <!-- Badge do Jogo -->
            <div class="product-detail-game">
                <img src="/assets/img/games/<?php echo sanitize($anuncio['jogo_icone'] ?? 'default-game.png'); ?>" 
                     alt="" onerror="this.style.display='none'">
                <span><?php echo sanitize($anuncio['jogo_nome']); ?></span>
            </div>

            <!-- Titulo -->
            <h1 class="product-detail-title"><?php echo sanitize($anuncio['titulo']); ?></h1>

            <!-- Meta -->
            <div class="product-detail-meta">
                <span><i class="fas fa-eye"></i> <?php echo $anuncio['visualizacoes']; ?> visualizacoes</span>
                <span><i class="fas fa-clock"></i> Publicado <?php echo time_ago($anuncio['criado_em']); ?></span>
            </div>

            <!-- Preco -->
            <div class="product-detail-price">
                <span class="price-label">Preco</span>
                <span class="price-value"><?php echo format_money($anuncio['preco']); ?></span>
            </div>

            <!-- Detalhes da Conta -->
            <div class="product-detail-specs">
                <h3><i class="fas fa-info-circle"></i> Detalhes da Conta</h3>
                <div class="specs-grid">
                    <?php if (!empty($anuncio['nivel_rank'])): ?>
                    <div class="spec-item">
                        <span class="spec-label">Rank/Nivel</span>
                        <span class="spec-value"><?php echo sanitize($anuncio['nivel_rank']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($anuncio['servidor'])): ?>
                    <div class="spec-item">
                        <span class="spec-label">Servidor</span>
                        <span class="spec-value"><?php echo sanitize($anuncio['servidor']); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($anuncio['itens_especiais'])): ?>
                    <div class="spec-item spec-full">
                        <span class="spec-label">Itens Especiais</span>
                        <span class="spec-value"><?php echo nl2br(sanitize($anuncio['itens_especiais'])); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Descricao -->
            <?php if (!empty($anuncio['descricao'])): ?>
            <div class="product-detail-desc">
                <h3><i class="fas fa-align-left"></i> Descricao</h3>
                <p><?php echo nl2br(sanitize($anuncio['descricao'])); ?></p>
            </div>
            <?php endif; ?>

            <!-- Botoes de Acao -->
            <div class="product-detail-actions">
                <a href="/painel/comprar.php?id=<?php echo $id; ?>" class="btn btn-green btn-lg">
                    <i class="fas fa-shopping-cart"></i> Comprar Agora
                </a>
                <button class="btn btn-outline btn-favorite <?php echo $is_favorite ? 'active' : ''; ?>" 
                        data-id="<?php echo $id; ?>" onclick="toggleFavorite(this)">
                    <i class="<?php echo $is_favorite ? 'fas' : 'far'; ?> fa-heart"></i>
                </button>
            </div>

            <!-- Garantia -->
            <div class="product-detail-guarantee">
                <i class="fas fa-shield-halved"></i>
                <div>
                    <strong>Garantia de <?php echo GARANTIA_HORAS; ?>h</strong>
                    <p>Se nao receber os dados corretos, voce pode solicitar reembolso.</p>
                </div>
            </div>

            <!-- Info do Vendedor -->
            <div class="seller-card">
                <div class="seller-header">
                    <div class="seller-avatar">
                        <?php if (!empty($anuncio['vendedor_avatar'])): ?>
                            <img src="/assets/img/uploads/avatares/<?php echo sanitize($anuncio['vendedor_avatar']); ?>" 
                                 alt="Avatar" style="width:100%; height:100%; object-fit:cover; border-radius:50%;">
                        <?php else: ?>
                            <i class="fas fa-user"></i>
                        <?php endif; ?>
                    </div>
                    <div class="seller-info">
                        <h4><?php echo sanitize($anuncio['vendedor_nome'] . ' ' . $anuncio['vendedor_sobrenome']); ?></h4>
                        <div class="seller-stats">
                            <span><i class="fas fa-star" style="color: var(--neon-yellow);"></i> <?php echo number_format($anuncio['vendedor_nota'] ?? 0, 1); ?></span>
                            <span><i class="fas fa-shopping-bag"></i> <?php echo $anuncio['vendedor_vendas']; ?> vendas</span>
                            <span><i class="fas fa-calendar"></i> Membro desde <?php echo date('M/Y', strtotime($anuncio['vendedor_desde'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Anuncios Similares -->
    <?php if (!empty($similares)): ?>
    <section class="similar-listings">
        <h2 class="section-title">Anuncios Similares</h2>
        <div class="products-grid">
            <?php foreach ($similares as $similar): ?>
            <a href="/loja/anuncio.php?id=<?php echo $similar['id']; ?>" class="product-card">
                <div class="product-image">
                    <?php
                    $sim_screenshots = json_decode($similar['screenshots'] ?? '[]', true);
                    $sim_img = !empty($sim_screenshots[0])
                        ? '/assets/img/uploads/anuncios/' . $sim_screenshots[0]
                        : '/assets/img/games/' . ($similar['jogo_icone'] ?? 'default-game.png');
                    ?>
                    <img src="<?php echo $sim_img; ?>" loading="lazy"
                         alt="<?php echo sanitize($similar['titulo']); ?>"
                         onerror="this.style.display='none'">
                </div>
                <div class="product-info">
                    <div class="product-game">
                        <?php echo sanitize($similar['jogo_nome']); ?>
                    </div>
                    <h3 class="product-title"><?php echo sanitize($similar['titulo']); ?></h3>
                    <div class="product-footer">
                        <div class="product-price"><?php echo format_money($similar['preco']); ?></div>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<script>
function changeImage(thumb, src) {
    document.getElementById('main-image').src = src;
    document.querySelectorAll('.gallery-thumb').forEach(t => t.classList.remove('active'));
    thumb.classList.add('active');
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
