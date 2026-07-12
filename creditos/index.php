<?php
/**
 * Asgard Store - Loja de Creditos
 */

$page_title = 'Loja de Creditos';
require_once __DIR__ . '/../includes/functions.php';

// Buscar jogos que tem creditos disponiveis
$jogos_com_creditos = db_fetch_all(
    "SELECT j.*, 
            COUNT(c.id) as total_pacotes,
            MIN(c.preco_final) as menor_preco,
            SUM(c.estoque) as total_estoque
     FROM jogos j
     INNER JOIN creditos c ON j.id = c.jogo_id
     WHERE j.ativo = 1 AND c.ativo = 1 AND c.estoque > 0
     GROUP BY j.id
     ORDER BY j.ordem ASC"
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <!-- Hero da Loja de Creditos -->
    <div class="credits-hero">
        <div class="credits-hero-content">
            <h1><i class="fas fa-coins"></i> Loja de Creditos</h1>
            <p>Compre diamantes, CP, Robux e muito mais diretamente pela Asgard Store!</p>
            <div class="credits-hero-features">
                <span><i class="fas fa-bolt"></i> Entrega Instantanea</span>
                <span><i class="fas fa-shield-halved"></i> 100% Seguro</span>
                <span><i class="fas fa-tag"></i> Melhores Precos</span>
            </div>
        </div>
    </div>

    <!-- Jogos com Creditos -->
    <div class="section-header" style="margin-top: 40px;">
        <div>
            <h2 class="section-title">Escolha o Jogo</h2>
            <p style="color: var(--text-secondary); margin-top: 5px;">Selecione o jogo para ver os pacotes disponiveis</p>
        </div>
    </div>

    <?php if (empty($jogos_com_creditos)): ?>
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-coins"></i></div>
            <h3 class="empty-state-title">Nenhum pacote disponivel</h3>
            <p class="empty-state-desc">Volte em breve! Novos creditos sendo adicionados.</p>
        </div>
    <?php else: ?>
    <div class="credits-grid">
        <?php foreach ($jogos_com_creditos as $jogo): ?>
        <a href="/creditos/jogo.php?jogo=<?php echo $jogo['slug']; ?>" class="credit-game-card">
            <div class="credit-game-icon">
                <img src="/assets/img/games/<?php echo sanitize($jogo['icone'] ?? 'default-game.png'); ?>" 
                     alt="<?php echo sanitize($jogo['nome']); ?>"
                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'">
                <div class="credit-game-fallback" style="display:none;">
                    <i class="fas fa-gamepad"></i>
                </div>
            </div>
            <div class="credit-game-info">
                <h3><?php echo sanitize($jogo['nome']); ?></h3>
                <div class="credit-game-meta">
                    <span><i class="fas fa-coins"></i> <?php echo $jogo['total_pacotes']; ?> pacotes</span>
                    <span><i class="fas fa-tag"></i> A partir de <?php echo format_money($jogo['menor_preco']); ?></span>
                </div>
                <div class="credit-game-currency">
                    Moeda: <?php echo sanitize($jogo['moeda_nome'] ?? 'Creditos'); ?>
                </div>
            </div>
            <div class="credit-game-arrow">
                <i class="fas fa-chevron-right"></i>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Como funciona -->
    <div class="card" style="margin-top: 50px; padding: 30px;">
        <h3 style="text-align: center; margin-bottom: 25px;"><i class="fas fa-info-circle" style="color: var(--neon-blue);"></i> Como Funciona</h3>
        <div class="how-it-works" style="grid-template-columns: repeat(3, 1fr);">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-icon"><i class="fas fa-gamepad"></i></div>
                <h3>Escolha o Jogo</h3>
                <p>Selecione o jogo e veja todos os pacotes de creditos disponiveis.</p>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-icon"><i class="fas fa-shopping-cart"></i></div>
                <h3>Faca seu Pedido</h3>
                <p>Escolha o pacote, pague via PIX ou Crypto e aguarde a confirmacao.</p>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-icon"><i class="fas fa-gift"></i></div>
                <h3>Receba seus Creditos</h3>
                <p>Receba o codigo/key do jogo e use na loja oficial!</p>
            </div>
        </div>
    </div>
</div>

<style>
.credits-hero {
    background: linear-gradient(135deg, rgba(255, 0, 64, 0.1), rgba(255, 0, 255, 0.1));
    border: 1px solid var(--border-color);
    border-radius: 20px;
    padding: 50px 40px;
    text-align: center;
}
.credits-hero h1 {
    font-family: var(--font-display);
    font-size: 2.2rem;
    margin-bottom: 15px;
}
.credits-hero h1 i {
    color: var(--neon-yellow);
    margin-right: 10px;
}
.credits-hero p {
    color: var(--text-secondary);
    font-size: 1.1rem;
    margin-bottom: 20px;
}
.credits-hero-features {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
}
.credits-hero-features span {
    color: var(--neon-green);
    font-weight: 600;
    font-size: 0.95rem;
}
.credits-hero-features i {
    margin-right: 5px;
}
.credits-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 20px;
}
.credit-game-card {
    display: flex;
    align-items: center;
    gap: 20px;
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 15px;
    padding: 20px 25px;
    transition: all 0.3s ease;
}
.credit-game-card:hover {
    border-color: var(--neon-green);
    transform: translateY(-3px);
    box-shadow: var(--shadow-green);
    text-decoration: none;
}
.credit-game-icon {
    width: 80px;
    height: 80px;
    flex-shrink: 0;
    border-radius: 15px;
    overflow: hidden;
    background: var(--bg-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
}
.credit-game-icon img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.credit-game-fallback {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    height: 100%;
    font-size: 2rem;
    color: var(--neon-green);
}
.credit-game-info {
    flex: 1;
}
.credit-game-info h3 {
    font-family: var(--font-heading);
    font-size: 1.2rem;
    margin-bottom: 8px;
}
.credit-game-meta {
    display: flex;
    gap: 15px;
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-bottom: 5px;
}
.credit-game-meta i {
    color: var(--neon-green);
    margin-right: 4px;
}
.credit-game-currency {
    color: var(--text-muted);
    font-size: 0.8rem;
}
.credit-game-arrow {
    color: var(--text-muted);
    font-size: 1.2rem;
    transition: all 0.3s ease;
}
.credit-game-card:hover .credit-game-arrow {
    color: var(--neon-green);
    transform: translateX(5px);
}
@media (max-width: 768px) {
    .credits-hero { padding: 30px 20px; }
    .credits-hero h1 { font-size: 1.5rem; }
    .credits-grid { grid-template-columns: 1fr; }
    .credit-game-card { flex-direction: column; text-align: center; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
