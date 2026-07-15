<?php
/**
 * Asgard Store - Painel: Saldo e Saque
 */

$page_title = 'Saldo e Saque';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];

// Buscar dados do usuario
$usuario = db_fetch("SELECT * FROM usuarios WHERE id = ?", [$user_id]);
$saldo = floatval($usuario['saldo'] ?? 0);

// Buscar configuracao de saque minimo
$config_saque = db_fetch("SELECT valor FROM configuracoes WHERE chave = 'minimo_saque'");
$minimo_saque = floatval($config_saque['valor'] ?? 30);

// Processar solicitacao de saque
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'solicitar_saque') {
        $valor = floatval($_POST['valor'] ?? 0);
        $metodo = $_POST['metodo'] ?? 'pix';
        $chave_pix = trim($_POST['chave_pix'] ?? '');
        $wallet_crypto = trim($_POST['wallet_crypto'] ?? '');

        // Validacoes
        if ($valor < $minimo_saque) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => "O valor minimo para saque e R$ " . number_format($minimo_saque, 2, ',', '.')];
            header('Location: /painel/saldo.php');
            exit;
        }

        if ($valor > $saldo) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Saldo insuficiente!'];
            header('Location: /painel/saldo.php');
            exit;
        }

        // Verificar se ja tem saque pendente
        $saque_pendente = db_fetch("SELECT id FROM saques WHERE usuario_id = ? AND status IN ('pendente', 'processando')", [$user_id]);
        if ($saque_pendente) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Voce ja tem um saque em andamento. Aguarde a finalizacao.'];
            header('Location: /painel/saldo.php');
            exit;
        }

        if ($metodo === 'pix' && empty($chave_pix)) {
            $chave_pix = $usuario['chave_pix'] ?? '';
        }

        if ($metodo === 'pix' && empty($chave_pix)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Informe sua chave PIX.'];
            header('Location: /painel/saldo.php');
            exit;
        }

        if ($metodo === 'crypto' && empty($wallet_crypto)) {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Informe sua wallet de crypto.'];
            header('Location: /painel/saldo.php');
            exit;
        }

        // Criar solicitacao de saque
        $dados_saque = [
            'usuario_id' => $user_id,
            'valor' => $valor,
            'metodo' => $metodo,
            'status' => 'pendente'
        ];

        if ($metodo === 'pix') {
            $dados_saque['chave_pix'] = $chave_pix;
        } else {
            $dados_saque['wallet_crypto'] = $wallet_crypto;
        }

        // Debitar saldo e criar saque, apenas se o debito realmente afetar uma linha
        $stmt = db_query("UPDATE usuarios SET saldo = saldo - ? WHERE id = ? AND saldo >= ?", [$valor, $user_id, $valor]);

        if ($stmt->rowCount() > 0) {
            db_insert('saques', $dados_saque);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Solicitacao de saque enviada com sucesso!'];
        } else {
            $_SESSION['flash'] = ['type' => 'error', 'message' => 'Saldo insuficiente!'];
        }
        header('Location: /painel/saldo.php');
        exit;
    }
}

// Buscar historico de saques
$saques = db_fetch_all("
    SELECT * FROM saques 
    WHERE usuario_id = ? 
    ORDER BY criado_em DESC 
    LIMIT 10
", [$user_id]);

// Buscar ultimas vendas (fonte do saldo)
$vendas = db_fetch_all("
    SELECT c.*, a.titulo as anuncio_titulo, j.nome as jogo_nome, j.icone as jogo_icone,
           u.nome as comprador_nome, u.sobrenome as comprador_sobrenome
    FROM compras c
    JOIN anuncios a ON c.anuncio_id = a.id
    JOIN jogos j ON a.jogo_id = j.id
    JOIN usuarios u ON c.comprador_id = u.id
    WHERE c.vendedor_id = ? AND c.status IN ('concluido', 'entregue')
    ORDER BY c.criado_em DESC
    LIMIT 5
", [$user_id]);

// Estatisticas
$total_ganho = db_fetch("SELECT COALESCE(SUM(valor_vendedor), 0) as total FROM compras WHERE vendedor_id = ? AND status IN ('concluido', 'entregue')", [$user_id])['total'];
$total_sacado = db_fetch("SELECT COALESCE(SUM(valor), 0) as total FROM saques WHERE usuario_id = ? AND status IN ('pago', 'processando')", [$user_id])['total'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <!-- Breadcrumb -->
    <nav class="breadcrumb">
        <a href="/">Inicio</a> <i class="fas fa-chevron-right"></i>
        <a href="/painel/">Painel</a> <i class="fas fa-chevron-right"></i>
        <span>Saldo e Saque</span>
    </nav>

    <!-- Header -->
    <h1 class="section-title" style="margin-bottom: 30px;">Saldo e Saque</h1>

    <!-- Card de Saldo -->
    <div class="saldo-card">
        <div class="saldo-main">
            <div class="saldo-icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="saldo-info">
                <span class="saldo-label">Saldo Disponivel</span>
                <span class="saldo-value">R$ <?php echo number_format($saldo, 2, ',', '.'); ?></span>
            </div>
        </div>
        <div class="saldo-stats">
            <div class="saldo-stat">
                <i class="fas fa-arrow-up" style="color: var(--neon-green);"></i>
                <div>
                    <span class="saldo-stat-value">R$ <?php echo number_format($total_ganho, 2, ',', '.'); ?></span>
                    <span class="saldo-stat-label">Total Ganho</span>
                </div>
            </div>
            <div class="saldo-stat">
                <i class="fas fa-arrow-down" style="color: var(--neon-yellow);"></i>
                <div>
                    <span class="saldo-stat-value">R$ <?php echo number_format($total_sacado, 2, ',', '.'); ?></span>
                    <span class="saldo-stat-label">Total Sacado</span>
                </div>
            </div>
            <div class="saldo-stat">
                <i class="fas fa-bullseye" style="color: var(--neon-blue);"></i>
                <div>
                    <span class="saldo-stat-value"><?php echo count($saques); ?></span>
                    <span class="saldo-stat-label">Saques Realizados</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Solicitar Saque -->
    <div class="saque-form-card">
        <h3><i class="fas fa-money-bill-wave" style="color: var(--neon-green); margin-right: 8px;"></i> Solicitar Saque</h3>
        <p style="color: var(--text-secondary); margin-bottom: 20px; font-size: 0.9rem;">
            Valor minimo: <strong>R$ <?php echo number_format($minimo_saque, 2, ',', '.'); ?></strong>
            <?php if ($saldo < $minimo_saque): ?>
            <span style="color: #ff4444; margin-left: 10px;">(Saldo insuficiente)</span>
            <?php endif; ?>
        </p>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="acao" value="solicitar_saque">

            <!-- Valor -->
            <div class="form-group">
                <label class="form-label">Valor do Saque (R$) *</label>
                <input type="number" name="valor" class="form-input" step="0.01" min="<?php echo $minimo_saque; ?>" max="<?php echo $saldo; ?>" 
                       placeholder="0,00" required <?php echo $saldo < $minimo_saque ? 'disabled' : ''; ?>>
            </div>

            <!-- Metodo -->
            <div class="form-group">
                <label class="form-label">Metodo de Pagamento *</label>
                <div class="metodo-options">
                    <label class="metodo-option">
                        <input type="radio" name="metodo" value="pix" checked>
                        <div class="metodo-card">
                            <i class="fas fa-qrcode"></i>
                            <span>PIX</span>
                            <small>Instantaneo</small>
                        </div>
                    </label>
                    <label class="metodo-option">
                        <input type="radio" name="metodo" value="crypto">
                        <div class="metodo-card">
                            <i class="fab fa-bitcoin"></i>
                            <span>Crypto</span>
                            <small>USDT, BTC, etc</small>
                        </div>
                    </label>
                </div>
            </div>

            <!-- Chave PIX (condicional) -->
            <div class="form-group pix-fields">
                <label class="form-label">Chave PIX *</label>
                <input type="text" name="chave_pix" class="form-input" 
                       value="<?php echo sanitize($usuario['chave_pix'] ?? ''); ?>"
                       placeholder="CPF, email, telefone ou chave aleatoria">
                <small style="color: var(--text-secondary);">Use a chave PIX cadastrada no seu perfil ou informe outra.</small>
            </div>

            <!-- Wallet Crypto (condicional) -->
            <div class="form-group crypto-fields" style="display: none;">
                <label class="form-label">Wallet Address *</label>
                <input type="text" name="wallet_crypto" class="form-input" 
                       placeholder="Endereco da carteira (USDT, BTC, etc)">
            </div>

            <button type="submit" class="btn btn-green btn-block" <?php echo $saldo < $minimo_saque ? 'disabled style="opacity: 0.5; cursor: not-allowed;"' : ''; ?>>
                <i class="fas fa-paper-plane"></i> Solicitar Saque
            </button>
        </form>
    </div>

    <!-- Historico de Saques -->
    <div class="saque-history-card">
        <h3><i class="fas fa-history" style="color: var(--neon-blue); margin-right: 8px;"></i> Historico de Saques</h3>

        <?php if (empty($saques)): ?>
        <div class="empty-state-small">
            <i class="fas fa-inbox"></i>
            <p>Nenhum saque solicitado ainda</p>
        </div>
        <?php else: ?>
        <div class="saque-list">
            <?php foreach ($saques as $saque): ?>
            <div class="saque-item">
                <div class="saque-item-left">
                    <div class="saque-metodo-icon <?php echo $saque['metodo']; ?>">
                        <i class="fas <?php echo $saque['metodo'] === 'pix' ? 'fa-qrcode' : 'fa-wallet'; ?>"></i>
                    </div>
                    <div class="saque-item-info">
                        <strong>R$ <?php echo number_format($saque['valor'], 2, ',', '.'); ?></strong>
                        <span><?php echo $saque['metodo'] === 'pix' ? 'PIX' : 'Crypto'; ?> • <?php echo time_ago($saque['criado_em']); ?></span>
                    </div>
                </div>
                <div class="saque-item-right">
                    <span class="saque-status status-<?php echo $saque['status']; ?>">
                        <?php
                        $saque_status = [
                            'pendente' => '⏳ Pendente',
                            'processando' => '🔄 Processando',
                            'pago' => '✅ Pago',
                            'rejeitado' => '❌ Rejeitado'
                        ];
                        echo $saque_status[$saque['status']] ?? $saque['status'];
                        ?>
                    </span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- Ultimas Vendas -->
    <?php if (!empty($vendas)): ?>
    <div class="vendas-card">
        <h3><i class="fas fa-shopping-bag" style="color: var(--neon-yellow); margin-right: 8px;"></i> Ultimas Vendas</h3>
        <div class="vendas-list">
            <?php foreach ($vendas as $venda): ?>
            <div class="venda-item">
                <div class="venda-image">
                    <?php
                    $anuncio = db_fetch("SELECT screenshots FROM anuncios WHERE id = ?", [$venda['anuncio_id']]);
                    $screenshots = json_decode($anuncio['screenshots'] ?? '[]', true);
                    $img = !empty($screenshots[0])
                        ? '/assets/img/uploads/anuncios/' . $screenshots[0]
                        : '/assets/img/games/' . ($venda['jogo_icone'] ?? 'default-game.png');
                    ?>
                    <img src="<?php echo $img; ?>" alt="" onerror="this.style.display='none'">
                </div>
                <div class="venda-info">
                    <strong><?php echo sanitize($venda['anuncio_titulo']); ?></strong>
                    <span><?php echo sanitize($venda['jogo_nome']); ?> • <?php echo sanitize($venda['comprador_nome'] . ' ' . $venda['comprador_sobrenome']); ?></span>
                    <span class="text-muted"><?php echo time_ago($venda['criado_em']); ?></span>
                </div>
                <div class="venda-valor">+ R$ <?php echo number_format($venda['valor_vendedor'], 2, ',', '.'); ?></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Toggle PIX/Crypto fields
document.querySelectorAll('input[name="metodo"]').forEach(function(radio) {
    radio.addEventListener('change', function() {
        var pixFields = document.querySelector('.pix-fields');
        var cryptoFields = document.querySelector('.crypto-fields');
        if (this.value === 'pix') {
            pixFields.style.display = 'block';
            cryptoFields.style.display = 'none';
        } else {
            pixFields.style.display = 'none';
            cryptoFields.style.display = 'block';
        }
    });
});
</script>

<style>
/* ============================================
   BALANCE & WITHDRAWAL STYLES
   ============================================ */

/* Saldo Card */
.saldo-card {
    background: linear-gradient(135deg, var(--bg-card), rgba(0,255,136,0.03));
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 25px;
}

.saldo-main {
    display: flex;
    align-items: center;
    gap: 20px;
    margin-bottom: 30px;
}

.saldo-icon {
    width: 70px;
    height: 70px;
    background: rgba(0,255,136,0.1);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 2rem;
    color: var(--neon-green);
}

.saldo-label {
    display: block;
    color: var(--text-secondary);
    font-size: 0.9rem;
    margin-bottom: 5px;
}

.saldo-value {
    font-family: 'Orbitron', monospace;
    font-size: 2.5rem;
    font-weight: 900;
    color: var(--neon-green);
    text-shadow: 0 0 30px rgba(0,255,136,0.3);
}

.saldo-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 20px;
    padding-top: 25px;
    border-top: 1px solid var(--border-color);
}

.saldo-stat {
    display: flex;
    align-items: center;
    gap: 12px;
}

.saldo-stat i {
    font-size: 1.2rem;
}

.saldo-stat-value {
    display: block;
    font-family: 'Orbitron', monospace;
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-primary);
}

.saldo-stat-label {
    font-size: 0.8rem;
    color: var(--text-secondary);
}

/* Saque Form Card */
.saque-form-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 25px;
}

.saque-form-card h3 {
    font-size: 1.1rem;
    margin-bottom: 15px;
}

/* Metodo Options */
.metodo-options {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

.metodo-option input[type="radio"] {
    display: none;
}

.metodo-card {
    background: var(--bg-secondary);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s;
}

.metodo-option input[type="radio"]:checked + .metodo-card {
    border-color: var(--neon-green);
    background: rgba(0,255,136,0.05);
}

.metodo-card:hover {
    border-color: var(--neon-green);
}

.metodo-card i {
    font-size: 1.5rem;
    display: block;
    margin-bottom: 10px;
    color: var(--neon-green);
}

.metodo-card span {
    display: block;
    font-weight: 600;
    margin-bottom: 5px;
}

.metodo-card small {
    color: var(--text-secondary);
    font-size: 0.8rem;
}

/* Saque History */
.saque-history-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 30px;
    margin-bottom: 25px;
}

.saque-history-card h3 {
    font-size: 1.1rem;
    margin-bottom: 20px;
}

.saque-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.saque-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    background: var(--bg-secondary);
    border-radius: 10px;
    border: 1px solid var(--border-color);
}

.saque-item-left {
    display: flex;
    align-items: center;
    gap: 15px;
}

.saque-metodo-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1rem;
}

.saque-metodo-icon.pix {
    background: rgba(0,212,255,0.1);
    color: var(--neon-blue);
}

.saque-metodo-icon.crypto {
    background: rgba(255,200,0,0.1);
    color: var(--neon-yellow);
}

.saque-item-info strong {
    display: block;
    font-family: 'Orbitron', monospace;
    font-size: 1rem;
    color: var(--text-primary);
}

.saque-item-info span {
    font-size: 0.8rem;
    color: var(--text-secondary);
}

.saque-status {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
}

.saque-status.status-pendente { background: rgba(255,200,0,0.15); color: var(--neon-yellow); }
.saque-status.status-processando { background: rgba(0,136,255,0.15); color: var(--neon-blue); }
.saque-status.status-pago { background: rgba(0,255,136,0.15); color: var(--neon-green); }
.saque-status.status-rejeitado { background: rgba(255,68,68,0.15); color: #ff4444; }

/* Vendas Card */
.vendas-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 16px;
    padding: 30px;
}

.vendas-card h3 {
    font-size: 1.1rem;
    margin-bottom: 20px;
}

.vendas-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.venda-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background: var(--bg-secondary);
    border-radius: 10px;
    border: 1px solid var(--border-color);
}

.venda-image {
    width: 60px;
    height: 45px;
    border-radius: 6px;
    overflow: hidden;
    background: var(--bg-primary);
    flex-shrink: 0;
}

.venda-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.venda-info {
    flex: 1;
    min-width: 0;
}

.venda-info strong {
    display: block;
    font-size: 0.9rem;
    color: var(--text-primary);
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.venda-info span {
    display: block;
    font-size: 0.8rem;
    color: var(--text-secondary);
}

.venda-valor {
    font-family: 'Orbitron', monospace;
    font-size: 0.95rem;
    font-weight: 700;
    color: var(--neon-green);
    white-space: nowrap;
}

.empty-state-small {
    text-align: center;
    padding: 30px;
    color: var(--text-secondary);
}

.empty-state-small i {
    font-size: 2rem;
    opacity: 0.3;
    display: block;
    margin-bottom: 10px;
}

/* Responsive */
@media (max-width: 768px) {
    .saldo-value {
        font-size: 1.8rem;
    }

    .saldo-stats {
        grid-template-columns: 1fr;
    }

    .metodo-options {
        grid-template-columns: 1fr;
    }

    .saque-item {
        flex-direction: column;
        align-items: flex-start;
        gap: 10px;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
