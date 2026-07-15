<?php
/**
 * Asgard Store - Pagina Inicial
 */

$page_title = 'Inicio';
require_once __DIR__ . '/includes/header.php';

// Buscar jogos ativos
$jogos = db_fetch_all(
    "SELECT j.*, (SELECT COUNT(*) FROM anuncios a WHERE a.jogo_id = j.id AND a.status = 'aprovado') as total_anuncios 
     FROM jogos j WHERE j.ativo = 1 ORDER BY j.ordem ASC"
);

// Criar array de jogos indexado por ID para facilitar busca
$jogos_por_id = [];
foreach ($jogos as $j) {
    $jogos_por_id[$j['id']] = $j;
}

// Buscar anuncios aprovados em destaque
$anuncios_destaque = db_fetch_all(
    "SELECT a.*, j.nome as jogo_nome, j.icone as jogo_icone, j.moeda_nome,
            u.nome as vendedor_nome, u.nota_media
     FROM anuncios a
     JOIN jogos j ON a.jogo_id = j.id
     JOIN usuarios u ON a.usuario_id = u.id
     WHERE a.status = 'aprovado'
     ORDER BY a.destaque DESC, a.criado_em DESC
     LIMIT 8"
);

// Buscar creditos mais vendidos
$creditos_populares = db_fetch_all(
    "SELECT c.*, j.nome as jogo_nome, j.icone as jogo_icone, j.moeda_icone
     FROM creditos c
     JOIN jogos j ON c.jogo_id = j.id
     WHERE c.ativo = 1 AND c.estoque > 0
     ORDER BY c.ordem ASC
     LIMIT 12"
);

// Estatisticas
$total_anuncios = db_count('anuncios', "status = 'aprovado'");
$total_usuarios = db_count('usuarios', "status = 'ativo'");
$total_vendas = db_count('compras', "status = 'concluido'");
$total_jogos = db_count('jogos', "ativo = 1");
?>

<!-- Hero Section -->
<section class="hero">
    <div class="container">
        <div class="hero-badge">
            <i class="fas fa-shield-halved"></i> Compra 100% Segura
        </div>
        <h1 class="hero-title">
            O Melhor Marketplace<br>
            de <span class="neon-green">Contas de Jogos</span>
        </h1>
        <p class="hero-subtitle">
            Compre e venda contas de Free Fire, COD Mobile, Roblox e muito mais. 
            Transacoes seguras via PIX e Criptomoeda.
        </p>
        <div class="hero-buttons">
            <a href="/loja/" class="btn btn-primary btn-lg">
                <i class="fas fa-store"></i> Explorar Loja
            </a>
            <a href="/creditos/" class="btn btn-green btn-lg">
                <i class="fas fa-coins"></i> Comprar Creditos
            </a>
            <a href="/pages/como-vender.php" class="btn btn-outline btn-lg">
                <i class="fas fa-hand-holding-dollar"></i> Comecar a Vender
            </a>
        </div>
    </div>
</section>

<!-- Stats Bar -->
<section class="container">
    <div class="stats-bar">
        <div class="stat-item">
            <div class="stat-number"><?php echo number_format($total_anuncios); ?>+</div>
            <div class="stat-label">Anuncios Ativos</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo number_format($total_usuarios); ?>+</div>
            <div class="stat-label">Usuarios Cadastrados</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo number_format($total_vendas); ?>+</div>
            <div class="stat-label">Vendas Realizadas</div>
        </div>
        <div class="stat-item">
            <div class="stat-number"><?php echo $total_jogos; ?></div>
            <div class="stat-label">Jogos Disponiveis</div>
        </div>
    </div>
</section>

<!-- Jogos Populares -->
<section class="container">
    <div class="section-header">
        <div>
            <h2 class="section-title">Jogos Populares</h2>
            <p style="color: var(--text-secondary); margin-top: 5px;">Escolha seu jogo favorito</p>
        </div>
        <a href="/loja/" class="btn btn-outline btn-sm">Ver Todos <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="games-grid">
        <?php foreach ($jogos as $jogo): ?>
        <a href="/loja/?jogo=<?php echo $jogo['slug']; ?>" class="game-card">
            <img loading="lazy" src="/assets/img/games/<?php echo sanitize($jogo['icone'] ?? 'default-game.png'); ?>" 
                 alt="<?php echo sanitize($jogo['nome']); ?>"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
            <div class="game-card-fallback" style="display:none; width:80px; height:80px; background:var(--bg-secondary); border-radius:50%; align-items:center; justify-content:center; margin:0 auto 15px;">
                <i class="fas fa-gamepad" style="font-size:2rem; color:var(--neon-green);"></i>
            </div>
            <div class="game-card-name"><?php echo sanitize($jogo['nome']); ?></div>
            <div class="game-card-count">
                <i class="fas fa-tag"></i> <?php echo $jogo['total_anuncios']; ?> anuncios
            </div>
        </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Anuncios em Destaque -->
<section class="container">
    <div class="section-header">
        <div>
            <h2 class="section-title">Anuncios em Destaque</h2>
            <p style="color: var(--text-secondary); margin-top: 5px;">Contas verificadas e aprovadas</p>
        </div>
        <a href="/loja/" class="btn btn-outline btn-sm">Ver Todos <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="products-grid">
        <?php if (empty($anuncios_destaque)): ?>
            <div class="empty-state" style="grid-column: 1/-1;">
                <div class="empty-state-icon"><i class="fas fa-inbox"></i></div>
                <h3 class="empty-state-title">Nenhum anuncio encontrado</h3>
                <p class="empty-state-desc">Seja o primeiro a criar um anuncio!</p>
                <a href="/painel/anuncio-novo.php" class="btn btn-primary">Criar Anuncio</a>
            </div>
        <?php else: ?>
            <?php foreach ($anuncios_destaque as $anuncio): ?>
            <a href="/loja/anuncio.php?id=<?php echo $anuncio['id']; ?>" class="product-card">
                <div class="product-image">
                    <?php 
                    $screenshots = json_decode($anuncio['screenshots'] ?? '[]', true);
                    $img = !empty($screenshots[0]) 
                        ? '/assets/img/uploads/anuncios/' . $screenshots[0] 
                        : '/assets/img/games/' . ($anuncio['jogo_icone'] ?? 'default-game.png');
                    ?>
                    <img src="<?php echo $img; ?>" alt="<?php echo sanitize($anuncio['titulo']); ?>"
                         onerror="this.style.display='none'">
                    <?php if (!empty($anuncio['destaque'])): ?>
                        <span class="product-badge">Destaque</span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <div class="product-game">
                        <img loading="lazy" src="/assets/img/games/<?php echo sanitize($anuncio['jogo_icone'] ?? 'default-game.png'); ?>" 
                             alt="" onerror="this.style.display='none'">
                        <?php echo sanitize($anuncio['jogo_nome']); ?>
                    </div>
                    <h3 class="product-title"><?php echo sanitize($anuncio['titulo']); ?></h3>
                    <div class="product-meta">
                        <span><i class="fas fa-star" style="color: var(--neon-yellow);"></i> <?php echo number_format($anuncio['nota_media'] ?? 0, 1); ?></span>
                        <span><i class="fas fa-eye"></i> <?php echo $anuncio['visualizacoes']; ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo time_ago($anuncio['criado_em']); ?></span>
                    </div>
                    <div class="product-footer">
                        <div class="product-price"><?php echo format_money($anuncio['preco']); ?></div>
                        <span class="btn btn-sm btn-primary">Ver Detalhes</span>
                    </div>
                </div>
            </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</section>

<!-- Creditos Populares -->
<section class="container" style="margin-top: 80px;">
    <div class="section-header">
        <div>
            <h2 class="section-title">Creditos para Jogos</h2>
            <p style="color: var(--text-secondary); margin-top: 5px;">Diamantes, CP, Robux e mais</p>
        </div>
        <a href="/creditos/" class="btn btn-outline btn-sm">Ver Todos <i class="fas fa-arrow-right"></i></a>
    </div>
    
    <div class="products-grid">
        <?php foreach ($creditos_populares as $credito): ?>
        <?php $jogo_info = $jogos_por_id[$credito['jogo_id']] ?? null; ?>
        <div class="credit-card">
            <img loading="lazy" src="/assets/img/moedas/<?php echo sanitize($credito['moeda_icone'] ?? 'default.png'); ?>" 
                 alt="<?php echo sanitize($credito['moeda_jogo']); ?>"
                 class="credit-icon"
                 onerror="this.style.display='none'">
            <div class="credit-game">
                <?php echo sanitize($credito['jogo_nome']); ?>
            </div>
            <div class="credit-amount">
                <?php echo number_format($credito['quantidade']); ?> <?php echo sanitize($credito['moeda_jogo']); ?>
            </div>
            <?php if ($credito['desconto_percentual'] > 0): ?>
                <div class="credit-original"><?php echo format_money($credito['preco_original']); ?></div>
                <div class="credit-discount">-<?php echo $credito['desconto_percentual']; ?>% OFF</div>
            <?php endif; ?>
            <div class="product-price" style="margin: 15px 0;">
                <?php echo format_money($credito['preco_final']); ?>
            </div>
            <a href="/creditos/comprar.php?id=<?php echo $credito['id']; ?>" class="btn btn-primary" style="width: 100%;">
                <i class="fas fa-shopping-cart"></i> Comprar
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Como Funciona -->
<section class="container" style="margin-top: 80px;">
    <div class="section-header" style="justify-content: center;">
        <div style="text-align: center;">
            <h2 class="section-title" style="text-align: center;">Como Funciona</h2>
            <p style="color: var(--text-secondary); margin-top: 5px;">Simples, rapido e seguro</p>
        </div>
    </div>
    
    <div class="how-it-works">
        <div class="step-card">
            <div class="step-number">1</div>
            <div class="step-icon"><i class="fas fa-user-plus"></i></div>
            <h3>Crie sua Conta</h3>
            <p>Cadastre-se gratuitamente e configure seu perfil com dados de pagamento.</p>
        </div>
        <div class="step-card">
            <div class="step-number">2</div>
            <div class="step-icon"><i class="fas fa-search"></i></div>
            <h3>Encontre o que Quer</h3>
            <p>Busque por jogo, rank ou preco. Filtre e encontre a conta perfeita.</p>
        </div>
        <div class="step-card">
            <div class="step-number">3</div>
            <div class="step-icon"><i class="fas fa-shield-halved"></i></div>
            <h3>Compre com Seguranca</h3>
            <p>Pague via PIX ou Crypto. O pagamento fica seguro ate a entrega.</p>
        </div>
        <div class="step-card">
            <div class="step-number">4</div>
            <div class="step-icon"><i class="fas fa-gamepad"></i></div>
            <h3>Acesse sua Conta</h3>
            <p>Receba os dados e aproveite! Garantia de 24h incluida.</p>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <div class="cta-content">
            <h2>Quer vender suas contas?</h2>
            <p>Cadastre-se como vendedor e comece a lucrar com suas contas de jogos.</p>
            <a href="/auth/cadastro.php" class="btn btn-green btn-lg">
                <i class="fas fa-rocket"></i> Comecar Agora
            </a>
        </div>
    </div>
</section>

<?php
$extra_scripts = '<script src="/assets/js/main.js"></script>';
require_once __DIR__ . '/includes/footer.php';
?>
