<?php
/**
 * Asgard Store - Meus Favoritos
 */

$page_title = 'Meus Favoritos';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];

// Remover dos favoritos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'remover') {
    require_csrf();
    $anuncio_id = intval($_POST['anuncio_id'] ?? 0);
    if ($anuncio_id > 0) {
        db_delete('favoritos', 'usuario_id = ? AND anuncio_id = ?', [$user_id, $anuncio_id]);
    }
    header('Location: ' . SITE_URL . '/painel/favoritos.php');
    exit;
}

// Contagem
$total_favoritos = db_count('favoritos', 'usuario_id = ?', [$user_id]);

// Paginacao
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 12;
$pagination = paginate($total_favoritos, $per_page, $page);

// Buscar favoritos com dados do anuncio
$favoritos = db_fetch_all(
    "SELECT f.*, a.titulo, a.preco, a.status, a.screenshots, a.visualizacoes, a.criado_em as anuncio_criado,
            j.nome as jogo_nome, j.icone as jogo_icone,
            u.nome as vendedor_nome, u.admin as vendedor_admin
     FROM favoritos f
     JOIN anuncios a ON f.anuncio_id = a.id
     JOIN jogos j ON a.jogo_id = j.id
     JOIN usuarios u ON a.usuario_id = u.id
     WHERE f.usuario_id = ? AND a.status = 'aprovado'
     ORDER BY f.criado_em DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    [$user_id]
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-heart" style="color: var(--neon-pink);"></i> Meus Favoritos
        <span style="color: var(--text-muted); font-size: 1rem; font-weight: 400;">(<?php echo $total_favoritos; ?>)</span>
    </h1>

    <?php if (empty($favoritos)): ?>
        <div class="card" style="text-align: center; padding: 60px;">
            <i class="fas fa-heart-broken" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px; display: block;"></i>
            <h3 style="color: var(--text); margin-bottom: 10px;">Nenhum favorito ainda</h3>
            <p style="color: var(--text-muted); margin-bottom: 20px;">Explore a loja e favorite os anuncios que mais te interessam.</p>
            <a href="/loja/" class="btn btn-primary"><i class="fas fa-store"></i> Ir para a Loja</a>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px;">
            <?php foreach ($favoritos as $fav): ?>
                <div class="card" style="position: relative; overflow: hidden;">
                    <!-- Imagem -->
                    <a href="/loja/anuncio.php?id=<?php echo $fav['anuncio_id']; ?>" style="text-decoration: none;">
                        <div style="height: 180px; background: var(--bg-input); border-radius: 8px; overflow: hidden; margin-bottom: 15px;">
                            <?php
                            $screenshots = json_decode($fav['screenshots'] ?? '[]', true) ?: [];
                            $img = !empty($screenshots[0]) ? "/assets/img/uploads/anuncios/{$screenshots[0]}" : "/assets/img/games/default-game.png";
                            ?>
                            <img src="<?php echo $img; ?>" alt="<?php echo sanitize($fav['titulo']); ?>"
                                 style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    </a>

                    <!-- Conteudo -->
                    <div style="padding: 0 5px;">
                        <!-- Jogo badge -->
                        <div style="display: flex; align-items: center; gap: 6px; margin-bottom: 8px;">
                            <span style="background: rgba(0,255,136,0.1); color: var(--neon-green); padding: 2px 8px; border-radius: 4px; font-size: 0.75rem;">
                                <?php echo sanitize($fav['jogo_nome']); ?>
                            </span>
                            <?php if ($fav['vendedor_admin']): ?>
                                <span style="background: rgba(0,123,255,0.1); color: var(--neon-blue); padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">
                                    💎 Oficial
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- Titulo -->
                        <h3 style="margin: 0 0 8px 0;">
                            <a href="/loja/anuncio.php?id=<?php echo $fav['anuncio_id']; ?>" 
                               style="color: var(--text); text-decoration: none; font-size: 0.95rem;">
                                <?php echo sanitize($fav['titulo']); ?>
                            </a>
                        </h3>

                        <!-- Info -->
                        <div style="display: flex; justify-content: space-between; align-items: center; color: var(--text-muted); font-size: 0.8rem; margin-bottom: 10px;">
                            <span><i class="fas fa-eye"></i> <?php echo $fav['visualizacoes']; ?></span>
                            <span><i class="fas fa-clock"></i> <?php echo time_ago($fav['anuncio_criado']); ?></span>
                        </div>

                        <!-- Preco + Acoes -->
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <span style="color: var(--neon-green); font-weight: 700; font-size: 1.1rem;">
                                R$ <?php echo number_format($fav['preco'], 2, ',', '.'); ?>
                            </span>
                            <div style="display: flex; gap: 5px;">
                                <a href="/loja/anuncio.php?id=<?php echo $fav['anuncio_id']; ?>" 
                                   class="btn btn-primary" style="padding: 5px 12px; font-size: 0.8rem;">
                                    <i class="fas fa-eye"></i> Ver
                                </a>
                                <form method="POST" style="margin: 0;" onsubmit="return confirm('Remover dos favoritos?')">
                                    <?php echo csrf_field(); ?>
                                    <input type="hidden" name="acao" value="remover">
                                    <input type="hidden" name="anuncio_id" value="<?php echo $fav['anuncio_id']; ?>">
                                    <button type="submit" class="btn btn-secondary" 
                                            style="padding: 5px 10px; font-size: 0.8rem; color: var(--neon-pink); border-color: var(--neon-pink);">
                                        <i class="fas fa-heart-broken"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Paginacao -->
        <?php if ($pagination['total_pages'] > 1): ?>
        <div style="margin-top: 30px; text-align: center;">
            <?php echo render_pagination($pagination, '/painel/favoritos.php?'); ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>