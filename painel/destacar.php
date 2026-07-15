<?php
/**
 * Asgard Store - Destacar Anuncio (Premium)
 */

$page_title = 'Destacar Anuncio';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$anuncio_id = intval($_GET['id'] ?? 0);

// Verificar se o anuncio pertence ao usuario
$anuncio = db_fetch(
    "SELECT a.*, j.nome as jogo_nome FROM anuncios a JOIN jogos j ON a.jogo_id = j.id WHERE a.id = ? AND a.usuario_id = ?",
    [$anuncio_id, $user_id]
);

if (!$anuncio) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Anuncio nao encontrado.'];
    header('Location: /painel/anuncios.php');
    exit;
}

// Verificar se ja esta em destaque ativo
$destaque_ativo = db_fetch(
    "SELECT id FROM destaques_premium WHERE anuncio_id = ? AND status = 'ativo' AND data_fim > NOW()",
    [$anuncio_id]
);

// Pegar precos
$preco_7 = floatval(db_fetch("SELECT valor FROM configuracoes WHERE chave = 'destaque_preco_7dias'")['valor'] ?? 9.99);
$preco_14 = floatval(db_fetch("SELECT valor FROM configuracoes WHERE chave = 'destaque_preco_14dias'")['valor'] ?? 14.99);
$preco_30 = floatval(db_fetch("SELECT valor FROM configuracoes WHERE chave = 'destaque_preco_30dias'")['valor'] ?? 19.99);

// Processar pagamento
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$destaque_ativo) {
    require_csrf();
    
    $duracao = intval($_POST['duracao'] ?? 7);
    $metodo = $_POST['metodo_pagamento'] ?? 'saldo';
    
    // Definir preco
    switch($duracao) {
        case 14: $valor = $preco_14; break;
        case 30: $valor = $preco_30; break;
        default: $valor = $preco_7;
    };
    
    // Verificar saldo
    if ($metodo === 'saldo') {
        $user = db_fetch("SELECT saldo FROM usuarios WHERE id = ?", [$user_id]);
        if ($user['saldo'] < $valor) {
            $error = 'Saldo insuficiente. Voce tem ' . format_money($user['saldo']);
        } else {
            // Debitar saldo
            db_query("UPDATE usuarios SET saldo = saldo - ? WHERE id = ? AND saldo >= ?", [$valor, $user_id, $valor]);
            
            // Criar destaque
            $data_fim = date('Y-m-d H:i:s', strtotime("+{$duracao} days"));
            db_insert('destaques_premium', [
                'anuncio_id' => $anuncio_id,
                'usuario_id' => $user_id,
                'valor_pago' => $valor,
                'duracao_dias' => $duracao,
                'data_fim' => $data_fim,
                'status' => 'ativo',
                'metodo_pagamento' => 'saldo'
            ]);
            
            // Atualizar anuncio
            db_update('anuncios', ['destaque' => 1], 'id = ?', [$anuncio_id]);
            
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anuncio destacado com sucesso!'];
            header('Location: /loja/anuncio.php?id=' . $anuncio_id);
            exit;
        }
    } else {
        // PIX/Crypto - criar como pendente
        $data_fim = date('Y-m-d H:i:s', strtotime("+{$duracao} days"));
        db_insert('destaques_premium', [
            'anuncio_id' => $anuncio_id,
            'usuario_id' => $user_id,
            'valor_pago' => $valor,
            'duracao_dias' => $duracao,
            'data_fim' => $data_fim,
            'status' => 'pendente',
            'metodo_pagamento' => $metodo
        ]);
        
        $_SESSION['flash'] = ['type' => 'info', 'message' => 'Aguardando confirmacao do pagamento.'];
        header('Location: /painel/anuncios.php');
        exit;
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px; max-width: 600px;">
    <h1 class="section-title" style="margin-bottom: 30px;">Destacar Anuncio</h1>
    
    <!-- Preview do Anuncio -->
    <div class="card" style="margin-bottom: 25px;">
        <div style="display: flex; gap: 15px; align-items: center;">
            <div style="width: 80px; height: 80px; background: var(--bg-secondary); border-radius: 10px; display: flex; align-items: center; justify-content: center;">
                <i class="fas fa-gamepad" style="font-size: 2rem; color: var(--neon-green);"></i>
            </div>
            <div>
                <div style="color: var(--neon-green); font-size: 0.85rem; font-weight: 600;">
                    <?php echo sanitize($anuncio['jogo_nome']); ?>
                </div>
                <h3 style="margin: 5px 0;"><?php echo sanitize($anuncio['titulo']); ?></h3>
                <div style="color: var(--neon-green); font-weight: 700;">
                    <?php echo format_money($anuncio['preco']); ?>
                </div>
            </div>
        </div>
    </div>
    
    <?php if ($destaque_ativo): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> Este anuncio ja esta em destaque!
            <br><small>Expira em: <?php echo date('d/m/Y H:i', strtotime($destaque_ativo['data_fim'])); ?></small>
        </div>
    <?php else: ?>
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="card" style="margin-bottom: 25px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-crown" style="color: var(--neon-yellow);"></i> Escolha a Duracao</h3>
            
            <form method="POST" action="/painel/destacar.php?id=<?php echo $anuncio_id; ?>">
                <?php echo csrf_field(); ?>
                
                <div class="plan-options">
                    <label class="plan-option">
                        <input type="radio" name="duracao" value="7" checked>
                        <div class="plan-card">
                            <div class="plan-duration">7 dias</div>
                            <div class="plan-price"><?php echo format_money($preco_7); ?></div>
                            <div class="plan-per-day">(<?php echo format_money($preco_7 / 7); ?>/dia)</div>
                        </div>
                    </label>
                    <label class="plan-option">
                        <input type="radio" name="duracao" value="14">
                        <div class="plan-card">
                            <div class="plan-duration">14 dias</div>
                            <div class="plan-price"><?php echo format_money($preco_14); ?></div>
                            <div class="plan-per-day">(<?php echo format_money($preco_14 / 14); ?>/dia)</div>
                        </div>
                    </label>
                    <label class="plan-option">
                        <input type="radio" name="duracao" value="30">
                        <div class="plan-card best-value">Melhor valor</div>
                        <div class="plan-card">
                            <div class="plan-duration">30 dias</div>
                            <div class="plan-price"><?php echo format_money($preco_30); ?></div>
                            <div class="plan-per-day">(<?php echo format_money($preco_30 / 30); ?>/dia)</div>
                        </div>
                    </label>
                </div>
                
                <h3 style="margin: 25px 0 15px;"><i class="fas fa-credit-card"></i> Metodo de Pagamento</h3>
                
                <div class="payment-options">
                    <label class="payment-option">
                        <input type="radio" name="metodo_pagamento" value="saldo" checked>
                        <div>
                            <strong>Saldo em Conta</strong>
                            <small>Disponivel: <?php echo format_money(current_user()['saldo'] ?? 0); ?></small>
                        </div>
                    </label>
                    <label class="payment-option">
                        <input type="radio" name="metodo_pagamento" value="pix">
                        <div>
                            <strong><i class="fas fa-qrcode"></i> PIX</strong>
                            <small>Pagamento manual</small>
                        </div>
                    </label>
                </div>
                
                <button type="submit" class="btn btn-green btn-block" style="margin-top: 25px;">
                    <i class="fas fa-crown"></i> Destacar Agora
                </button>
            </form>
        </div>
        
        <div class="card">
            <h4 style="margin-bottom: 15px;"><i class="fas fa-info-circle" style="color: var(--neon-blue);"></i> Beneficios do Destaque</h4>
            <ul style="list-style: none; padding: 0;">
                <li style="padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                    <i class="fas fa-check" style="color: var(--neon-green); margin-right: 10px;"></i>
                    Aparece no topo da loja
                </li>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                    <i class="fas fa-check" style="color: var(--neon-green); margin-right: 10px;"></i>
                    Badge de destaque visivel
                </li>
                <li style="padding: 8px 0; border-bottom: 1px solid var(--border-color);">
                    <i class="fas fa-check" style="color: var(--neon-green); margin-right: 10px;"></i>
                    Mais visualizacoes e compras
                </li>
                <li style="padding: 8px 0;">
                    <i class="fas fa-check" style="color: var(--neon-green); margin-right: 10px;"></i>
                    Prioridade nos resultados de busca
                </li>
            </ul>
        </div>
    <?php endif; ?>
</div>

<style>
.plan-options {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 15px;
}
.plan-option input { display: none; }
.plan-card {
    background: var(--bg-secondary);
    border: 2px solid var(--border-color);
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s ease;
}
.plan-option input:checked + .plan-card {
    border-color: var(--neon-green);
    box-shadow: var(--shadow-green);
}
.plan-duration {
    font-family: var(--font-heading);
    font-size: 1.1rem;
    font-weight: 600;
    margin-bottom: 10px;
}
.plan-price {
    font-family: var(--font-display);
    font-size: 1.5rem;
    font-weight: 900;
    color: var(--neon-green);
}
.plan-per-day {
    color: var(--text-muted);
    font-size: 0.8rem;
    margin-top: 5px;
}
.plan-card.best-value {
    position: absolute;
    top: -12px;
    left: 50%;
    transform: translateX(-50%);
    background: var(--neon-yellow);
    color: #000;
    padding: 2px 12px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 700;
}
.plan-option { position: relative; }
.payment-options {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.payment-option input { display: none; }
.payment-option div {
    display: flex;
    align-items: center;
    justify-content: space-between;
    background: var(--bg-secondary);
    border: 2px solid var(--border-color);
    border-radius: 10px;
    padding: 15px;
    cursor: pointer;
    transition: all 0.3s ease;
}
.payment-option input:checked + div {
    border-color: var(--neon-green);
}
@media (max-width: 768px) {
    .plan-options { grid-template-columns: 1fr; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
