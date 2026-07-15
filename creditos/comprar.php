<?php
/**
 * Asgard Store - Comprar Creditos
 * Fluxo completo: selecionar pacote → pagamento → entrega do codigo
 */

$page_title = 'Comprar Creditos';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Filtro opcional por jogo (vindo de /creditos/?jogo=slug)
$jogo_slug = trim($_GET['jogo'] ?? '');
$jogo_filtro = null;
if ($jogo_slug !== '') {
    $jogo_filtro = db_fetch("SELECT * FROM jogos WHERE slug = ?", [$jogo_slug]);
}

// Buscar pacotes ativos
if ($jogo_filtro) {
    $creditos = db_fetch_all(
        "SELECT c.*, j.nome as jogo_nome, j.icone as jogo_icone 
         FROM creditos c 
         JOIN jogos j ON c.jogo_id = j.id 
         WHERE c.ativo = 1 AND c.estoque > 0 AND c.jogo_id = ?
         ORDER BY c.ordem ASC",
        [$jogo_filtro['id']]
    );
} else {
    $creditos = db_fetch_all(
        "SELECT c.*, j.nome as jogo_nome, j.icone as jogo_icone 
         FROM creditos c 
         JOIN jogos j ON c.jogo_id = j.id 
         WHERE c.ativo = 1 AND c.estoque > 0 
         ORDER BY j.ordem ASC, c.ordem ASC"
    );
}

// Processar compra
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $credito_id = intval($_POST['credito_id'] ?? 0);
    $quantidade = max(1, intval($_POST['quantidade'] ?? 1));
    $metodo = $_POST['metodo_pagamento'] ?? 'pix';
    $comprovante = sanitize($_POST['comprovante'] ?? '');

    $credito = db_fetch("SELECT * FROM creditos WHERE id = ? AND ativo = 1 AND estoque > 0", [$credito_id]);

    if (!$credito) {
        $error = 'Pacote nao encontrado ou sem estoque.';
    } elseif (!in_array($metodo, ['pix', 'crypto'])) {
        $error = 'Metodo de pagamento invalido.';
    } else {
        $valor_total = $credito['preco_final'] * $quantidade;

        // Gerar codigo unico
        $codigo = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));

        $compra_id = db_insert('compra_creditos', [
            'usuario_id' => $user_id,
            'credito_id' => $credito_id,
            'quantidade' => $quantidade,
            'valor_pago' => $valor_total,
            'metodo_pagamento' => $metodo,
            'comprovante' => $comprovante,
            'codigo_entregue' => $codigo,
            'status' => 'pendente',
        ]);

        if ($compra_id) {
            // Decrementar estoque
            db_query("UPDATE creditos SET estoque = estoque - ? WHERE id = ?", [$quantidade, $credito_id]);

            // Notificar admins
            $admins = db_fetch_all("SELECT id FROM usuarios WHERE admin = 1");
            foreach ($admins as $admin) {
                create_notification($admin['id'], 'Nova Compra de Creditos', "Compra #{$compra_id} - {$credito['nome']} x{$quantidade} - R$ " . number_format($valor_total, 2, ',', '.'), 'compra', '/admin/compras.php');
            }

            $_SESSION['flash'] = ['type' => 'success', 'message' => "Compra realizada! Seu codigo: {$codigo}"];
            header('Location: ' . SITE_URL . '/creditos/?compra=' . $compra_id);
            exit;
        } else {
            $error = 'Erro ao processar compra.';
        }
    }
}

// Verificar compra concluida
$compra_id_check = intval($_GET['compra'] ?? 0);
$compra_check = null;
if ($compra_id_check > 0) {
    $compra_check = db_fetch(
        "SELECT cc.*, cr.nome as credito_nome, cr.quantidade as creditos_quantidade, j.nome as jogo_nome
         FROM compra_creditos cc
         JOIN creditos cr ON cc.credito_id = cr.id
         JOIN jogos j ON cr.jogo_id = j.id
         WHERE cc.id = ? AND cc.usuario_id = ?",
        [$compra_id_check, $user_id]
    );
}

// Historico de compras
$historico = db_fetch_all(
    "SELECT cc.*, cr.nome as credito_nome, j.nome as jogo_nome
     FROM compra_creditos cc
     JOIN creditos cr ON cc.credito_id = cr.id
     JOIN jogos j ON cr.jogo_id = j.id
     WHERE cc.usuario_id = ?
     ORDER BY cc.criado_em DESC
     LIMIT 10",
    [$user_id]
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <h1 class="section-title" style="text-align: center; margin-bottom: 10px;">
        <i class="fas fa-coins" style="color: var(--neon-green);"></i>
        <?php echo $jogo_filtro ? 'Creditos de ' . sanitize($jogo_filtro['nome']) : 'Comprar Creditos'; ?>
    </h1>
    <p style="text-align: center; color: var(--text-muted); margin-bottom: 30px;">
        Compre creditos para seus jogos favoritos com seguranca.
    </p>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Compra concluida -->
    <?php if ($compra_check): ?>
    <div class="card" style="padding: 30px; text-align: center; margin-bottom: 30px; border: 2px solid var(--neon-green);">
        <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--neon-green); margin-bottom: 15px; display: block;"></i>
        <h2 style="color: var(--text); margin-bottom: 10px;">Compra Realizada!</h2>
        <p style="color: var(--text-muted); margin-bottom: 20px;">
            <?php echo sanitize($compra_check['jogo_nome']); ?> - <?php echo sanitize($compra_check['credito_nome']); ?>
        </p>
        <div style="background: var(--bg-input); padding: 20px; border-radius: 12px; margin-bottom: 20px;">
            <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 5px;">Seu Codigo:</p>
            <p style="color: var(--neon-green); font-size: 1.5rem; font-weight: 700; font-family: monospace; letter-spacing: 2px;">
                <?php echo sanitize($compra_check['codigo_entregue']); ?>
            </p>
        </div>
        <div style="color: var(--text-muted); font-size: 0.9rem;">
            <p><i class="fas fa-coins"></i> Quantidade: <?php echo $compra_check['creditos_quantidade']; ?></p>
            <p><i class="fas fa-dollar-sign"></i> Valor: R$ <?php echo number_format($compra_check['valor_pago'], 2, ',', '.'); ?></p>
            <p><i class="fas fa-clock"></i> Status: <?php echo ucfirst($compra_check['status']); ?></p>
        </div>
    </div>
    <?php endif; ?>

    <!-- Pacotes -->
    <?php if (empty($creditos)): ?>
        <div class="card" style="text-align: center; padding: 50px;">
            <i class="fas fa-coins" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px; display: block;"></i>
            <h3 style="color: var(--text); margin-bottom: 10px;">Nenhum pacote disponivel</h3>
            <p style="color: var(--text-muted);">Volte em breve para novos pacotes!</p>
        </div>
    <?php else: ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-bottom: 40px;">
            <?php foreach ($creditos as $c): ?>
            <div class="card" style="padding: 25px; position: relative;">
                <?php if ($c['desconto_percentual'] > 0): ?>
                    <div style="position: absolute; top: 10px; right: 10px; background: var(--neon-pink); color: #fff; padding: 3px 10px; border-radius: 12px; font-size: 0.75rem; font-weight: 700;">
                        -<?php echo $c['desconto_percentual']; ?>%
                    </div>
                <?php endif; ?>

                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 15px;">
                    <img src="/assets/img/games/<?php echo $c['icone']; ?>" style="width: 40px; height: 40px; border-radius: 8px;" onerror="this.style.display='none'">
                    <div>
                        <h3 style="color: var(--text); margin: 0; font-size: 1rem;"><?php echo sanitize($c['jogo_nome']); ?></h3>
                        <p style="color: var(--neon-green); margin: 0; font-weight: 600;"><?php echo sanitize($c['nome']); ?></p>
                    </div>
                </div>

                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 10px;">
                    <?php echo number_format($c['quantidade']) . ' ' . sanitize($c['moeda_jogo'] ?? 'creditos'); ?>
                </p>

                <?php if ($c['desconto_percentual'] > 0): ?>
                    <span style="color: var(--text-muted); text-decoration: line-through; font-size: 0.85rem;">
                        R$ <?php echo number_format($c['preco_original'], 2, ',', '.'); ?>
                    </span>
                <?php endif; ?>
                <div style="color: var(--neon-green); font-size: 1.3rem; font-weight: 700; margin: 5px 0;">
                    R$ <?php echo number_format($c['preco_final'], 2, ',', '.'); ?>
                </div>

                <p style="color: var(--text-muted); font-size: 0.8rem;">
                    <i class="fas fa-box"></i> Estoque: <?php echo $c['estoque']; ?> restante(s)
                </p>

                <!-- Botao Comprar -->
                <button onclick="abrirModalCompra(<?php echo $c['id']; ?>, '<?php echo sanitize(addslashes($c['nome'])); ?>', '<?php echo sanitize(addslashes($c['jogo_nome'])); ?>', <?php echo $c['preco_final']; ?>, <?php echo $c['estoque']; ?>)"
                        class="btn btn-primary" style="width: 100%; margin-top: 15px;">
                    <i class="fas fa-shopping-cart"></i> Comprar
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- Historico -->
    <?php if (!empty($historico)): ?>
    <div class="card" style="padding: 20px;">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-history" style="color: var(--neon-blue);"></i> Minhas Compras Recentes</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="text-align: left; padding: 8px; color: var(--text-muted); font-size: 0.8rem;">Jogo</th>
                        <th style="text-align: left; padding: 8px; color: var(--text-muted); font-size: 0.8rem;">Pacote</th>
                        <th style="text-align: left; padding: 8px; color: var(--text-muted); font-size: 0.8rem;">Codigo</th>
                        <th style="text-align: right; padding: 8px; color: var(--text-muted); font-size: 0.8rem;">Valor</th>
                        <th style="text-align: center; padding: 8px; color: var(--text-muted); font-size: 0.8rem;">Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($historico as $h): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 8px; color: var(--text); font-size: 0.85rem;"><?php echo sanitize($h['jogo_nome']); ?></td>
                        <td style="padding: 8px; color: var(--text); font-size: 0.85rem;"><?php echo sanitize($h['credito_nome']); ?></td>
                        <td style="padding: 8px; font-family: monospace; color: var(--neon-green); font-size: 0.85rem;"><?php echo sanitize($h['codigo_entregue']); ?></td>
                        <td style="padding: 8px; text-align: right; color: var(--text); font-size: 0.85rem;">R$ <?php echo number_format($h['valor_pago'], 2, ',', '.'); ?></td>
                        <td style="padding: 8px; text-align: center;">
                            <?php
                            $s_colors = ['pendente' => 'var(--neon-yellow)', 'confirmado' => 'var(--neon-green)', 'cancelado' => 'var(--neon-pink)'];
                            $sc = $s_colors[$h['status']] ?? 'var(--text-muted)';
                            ?>
                            <span style="color: <?php echo $sc; ?>; font-size: 0.8rem; font-weight: 600;"><?php echo ucfirst($h['status']); ?></span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Compra -->
<div id="modal-compra" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 1000; align-items: center; justify-content: center;">
    <div class="card" style="width: 90%; max-width: 500px; padding: 30px; position: relative;">
        <button onclick="fecharModal()" style="position: absolute; top: 10px; right: 15px; background: none; border: none; color: var(--text-muted); font-size: 1.5rem; cursor: pointer;">&times;</button>

        <h3 style="margin-bottom: 5px; color: var(--text);">Confirmar Compra</h3>
        <p id="modal-jogo" style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;"></p>

        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="credito_id" id="modal-credito-id">

            <div class="form-group">
                <label class="form-label">Quantidade</label>
                <input type="number" name="quantidade" id="modal-quantidade" class="form-input" min="1" value="1"
                       oninput="atualizarTotal()">
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 3px;">
                    Estoque: <span id="modal-estoque"></span>
                </p>
            </div>

            <div class="form-group">
                <label class="form-label">Metodo de Pagamento *</label>
                <select name="metodo_pagamento" class="form-select" required>
                    <option value="pix">PIX</option>
                    <option value="crypto">Crypto</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Comprovante / Hash da Transacao</label>
                <input type="text" name="comprovante" class="form-input" placeholder="Cole aqui o comprovante">
            </div>

            <div style="background: var(--bg-input); padding: 15px; border-radius: 8px; margin-bottom: 20px; text-align: center;">
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 5px;">Total:</p>
                <p style="color: var(--neon-green); font-size: 1.5rem; font-weight: 700;">R$ <span id="modal-total">0,00</span></p>
            </div>

            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                <i class="fas fa-check"></i> Confirmar Compra
            </button>
        </form>
    </div>
</div>

<script>
let precoUnitario = 0;

function abrirModalCompra(id, nome, jogo, preco, estoque) {
    document.getElementById('modal-credito-id').value = id;
    document.getElementById('modal-jogo').textContent = jogo + ' — ' + nome;
    document.getElementById('modal-estoque').textContent = estoque;
    document.getElementById('modal-quantidade').max = estoque;
    document.getElementById('modal-quantidade').value = 1;
    precoUnitario = preco;
    atualizarTotal();
    document.getElementById('modal-compra').style.display = 'flex';
}

function fecharModal() {
    document.getElementById('modal-compra').style.display = 'none';
}

function atualizarTotal() {
    const qtd = parseInt(document.getElementById('modal-quantidade').value) || 1;
    const total = (precoUnitario * qtd).toLocaleString('pt-BR', {minimumFractionDigits: 2});
    document.getElementById('modal-total').textContent = total;
}

// Fechar modal com ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') fecharModal();
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>