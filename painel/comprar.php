<?php
/**
 * Asgard Store - Comprar Conta
 * Fluxo de compra de um anuncio (conta de jogo)
 */

$page_title = 'Comprar Conta';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$error = '';

$id = intval($_GET['id'] ?? 0);
if ($id <= 0) {
    header('Location: ' . SITE_URL . '/loja/');
    exit;
}

// Buscar anuncio
$anuncio = db_fetch(
    "SELECT a.*, j.nome as jogo_nome, j.icone as jogo_icone,
            u.id as vendedor_id, u.nome as vendedor_nome, u.sobrenome as vendedor_sobrenome
     FROM anuncios a
     JOIN jogos j ON a.jogo_id = j.id
     JOIN usuarios u ON a.usuario_id = u.id
     WHERE a.id = ? AND a.status = 'aprovado'",
    [$id]
);

if (!$anuncio) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Anuncio nao encontrado ou nao esta mais disponivel.'];
    header('Location: ' . SITE_URL . '/loja/');
    exit;
}

// Nao pode comprar o proprio anuncio
if ($anuncio['vendedor_id'] == $user_id) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Voce nao pode comprar o proprio anuncio.'];
    header('Location: ' . SITE_URL . '/loja/anuncio.php?id=' . $id);
    exit;
}

// Processar compra
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $metodo = $_POST['metodo_pagamento'] ?? '';
    $comprovante = sanitize($_POST['comprovante'] ?? '');
    $wallet = sanitize($_POST['wallet_crypto'] ?? '');

    if (!in_array($metodo, ['saldo', 'pix', 'crypto'])) {
        $error = 'Metodo de pagamento invalido.';
    } else {
        // Trava contra dupla venda: reconfirma que o anuncio ainda esta aprovado
        $ainda_disponivel = db_fetch("SELECT id FROM anuncios WHERE id = ? AND status = 'aprovado'", [$id]);

        if (!$ainda_disponivel) {
            $error = 'Este anuncio acabou de ser vendido para outro comprador.';
        } else {
            $valor = (float) $anuncio['preco'];
            $comissao = round($valor * (COMISSAO_PADRAO / 100), 2);
            $valor_vendedor = $valor - $comissao;

            $dados_compra = [
                'anuncio_id' => $id,
                'comprador_id' => $user_id,
                'vendedor_id' => $anuncio['vendedor_id'],
                'valor' => $valor,
                'comissao' => $comissao,
                'valor_vendedor' => $valor_vendedor,
                'metodo_pagamento' => $metodo === 'saldo' ? 'pix' : $metodo, // coluna aceita apenas pix/crypto
            ];

            $compra_id = null;

            if ($metodo === 'saldo') {
                $user = db_fetch("SELECT saldo FROM usuarios WHERE id = ?", [$user_id]);
                if (!$user || $user['saldo'] < $valor) {
                    $error = 'Saldo insuficiente. Seu saldo: ' . format_money($user['saldo'] ?? 0);
                } else {
                    $stmt = db_query("UPDATE usuarios SET saldo = saldo - ? WHERE id = ? AND saldo >= ?", [$valor, $user_id, $valor]);
                    if ($stmt->rowCount() > 0) {
                        $dados_compra['status'] = 'pagamento_confirmado';
                        $compra_id = db_insert('compras', $dados_compra);
                    } else {
                        $error = 'Saldo insuficiente.';
                    }
                }
            } else {
                if ($metodo === 'crypto') {
                    if (empty($wallet)) {
                        $error = 'Informe a carteira/hash da transacao.';
                    } else {
                        $dados_compra['wallet_crypto'] = $wallet;
                    }
                } else { // pix
                    if (empty($comprovante)) {
                        $error = 'Informe o comprovante do PIX.';
                    } else {
                        $dados_compra['comprovante_pix'] = $comprovante;
                    }
                }

                if (empty($error)) {
                    $dados_compra['status'] = 'aguardando_pagamento';
                    $compra_id = db_insert('compras', $dados_compra);
                }
            }

            if ($compra_id) {
                // Reserva o anuncio (tira da vitrine) para evitar venda dupla
                db_update('anuncios', ['status' => 'vendido'], 'id = ?', [$id]);

                // Notifica o vendedor
                create_notification(
                    $anuncio['vendedor_id'],
                    'Novo pedido de compra!',
                    'Seu anuncio "' . $anuncio['titulo'] . '" recebeu um pedido de compra.',
                    'compra',
                    '/painel/vendas.php'
                );

                // Notifica admins quando o pagamento ainda precisa de confirmacao manual
                if ($dados_compra['status'] === 'aguardando_pagamento') {
                    $admins = db_fetch_all("SELECT id FROM usuarios WHERE admin = 1");
                    foreach ($admins as $admin) {
                        create_notification(
                            $admin['id'],
                            'Novo pagamento para confirmar',
                            'Compra #' . $compra_id . ' aguardando confirmacao de pagamento (' . $metodo . ').',
                            'compra',
                            '/admin/compras.php'
                        );
                    }
                }

                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Compra realizada com sucesso! Acompanhe o status em "Minhas Compras".'];
                header('Location: ' . SITE_URL . '/painel/compras.php');
                exit;
            } elseif (empty($error)) {
                $error = 'Erro ao processar a compra. Tente novamente.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';

$comissao_preview = round($anuncio['preco'] * (COMISSAO_PADRAO / 100), 2);
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px; max-width: 600px;">
    <h1 class="section-title" style="margin-bottom: 30px;">Finalizar Compra</h1>

    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo sanitize($error); ?></div>
    <?php endif; ?>

    <!-- Preview do Anuncio -->
    <div class="card" style="margin-bottom: 25px; padding: 20px;">
        <div style="display: flex; gap: 15px; align-items: center;">
            <div style="width: 70px; height: 70px; background: var(--bg-secondary); border-radius: 10px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                <i class="fas fa-gamepad" style="font-size: 1.8rem; color: var(--neon-green);"></i>
            </div>
            <div>
                <div style="color: var(--neon-green); font-size: 0.85rem; font-weight: 600;">
                    <?php echo sanitize($anuncio['jogo_nome']); ?>
                </div>
                <h3 style="margin: 5px 0;"><?php echo sanitize($anuncio['titulo']); ?></h3>
                <div style="color: var(--text-muted); font-size: 0.85rem;">
                    Vendedor: <?php echo sanitize($anuncio['vendedor_nome'] . ' ' . $anuncio['vendedor_sobrenome']); ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumo de valores -->
    <div class="card" style="margin-bottom: 25px; padding: 20px;">
        <div style="display: flex; justify-content: space-between; padding: 6px 0; color: var(--text-muted);">
            <span>Valor do anuncio</span>
            <span><?php echo format_money($anuncio['preco']); ?></span>
        </div>
        <div style="display: flex; justify-content: space-between; padding: 6px 0; border-top: 1px solid var(--border-color); margin-top: 6px; color: var(--text); font-weight: 700; font-size: 1.2rem;">
            <span>Total a pagar</span>
            <span style="color: var(--neon-green);"><?php echo format_money($anuncio['preco']); ?></span>
        </div>
    </div>

    <!-- Formulario de Pagamento -->
    <form method="POST" class="card" style="padding: 20px;">
        <?php echo csrf_field(); ?>

        <div class="form-group">
            <label class="form-label">Metodo de Pagamento *</label>
            <select name="metodo_pagamento" id="metodo_pagamento" class="form-select" required onchange="atualizarCampos()">
                <option value="saldo">Saldo da conta</option>
                <option value="pix">PIX</option>
                <option value="crypto">Crypto</option>
            </select>
        </div>

        <div class="form-group" id="campo-comprovante">
            <label class="form-label">Comprovante do PIX</label>
            <input type="text" name="comprovante" class="form-input" placeholder="Cole aqui o codigo/comprovante do PIX">
        </div>

        <div class="form-group" id="campo-wallet" style="display: none;">
            <label class="form-label">Carteira / Hash da Transacao</label>
            <input type="text" name="wallet_crypto" class="form-input" placeholder="Endereco da carteira ou hash">
        </div>

        <button type="submit" class="btn btn-primary btn-lg" style="width: 100%; margin-top: 10px;">
            <i class="fas fa-check"></i> Confirmar Compra
        </button>

        <p style="color: var(--text-muted); font-size: 0.8rem; text-align: center; margin-top: 15px;">
            Ao confirmar, o anuncio fica reservado para voce e o vendedor sera notificado.
        </p>
    </form>
</div>

<script>
function atualizarCampos() {
    const metodo = document.getElementById('metodo_pagamento').value;
    document.getElementById('campo-comprovante').style.display = metodo === 'pix' ? 'block' : 'none';
    document.getElementById('campo-wallet').style.display = metodo === 'crypto' ? 'block' : 'none';
}
atualizarCampos();
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
