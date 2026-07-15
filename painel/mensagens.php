<?php
/**
 * Asgard Store - Mensagens por Compra (Chat)
 */

$page_title = 'Mensagens';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$compra_id = intval($_GET['compra_id'] ?? 0);

if ($compra_id <= 0) {
    header('Location: ' . SITE_URL . '/painel/compras.php');
    exit;
}

// Verificar acesso (comprador ou vendedor)
$compra = db_fetch(
    "SELECT c.*, a.titulo as anuncio_titulo,
            u_comp.nome as comprador_nome, u_vend.nome as vendedor_nome
     FROM compras c
     JOIN anuncios a ON c.anuncio_id = a.id
     JOIN usuarios u_comp ON c.comprador_id = u_comp.id
     JOIN usuarios u_vend ON c.vendedor_id = u_vend.id
     WHERE c.id = ? AND (c.comprador_id = ? OR c.vendedor_id = ?)",
    [$compra_id, $user_id, $user_id]
);

if (!$compra) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Compra nao encontrada.'];
    header('Location: ' . SITE_URL . '/painel/compras.php');
    exit;
}

$is_comprador = ($compra['comprador_id'] == $user_id);
$outra_parte = $is_comprador ? $compra['vendedor_nome'] : $compra['comprador_nome'];
$outra_parte_id = $is_comprador ? $compra['vendedor_id'] : $compra['comprador_id'];

// Enviar mensagem
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $conteudo = sanitize($_POST['conteudo'] ?? '');
    if (!empty($conteudo) && strlen($conteudo) >= 1) {
        db_insert('mensagens', [
            'compra_id' => $compra_id,
            'remetente_id' => $user_id,
            'conteudo' => $conteudo,
            'lida' => 0,
        ]);

        // Notificar outra parte
        create_notification(
            $outra_parte_id,
            'Nova Mensagem',
            "Nova mensagem na compra #{$compra_id}: " . mb_substr($conteudo, 0, 50) . "...",
            'compra',
            "/painel/mensagens.php?compra_id={$compra_id}"
        );
    }

    header('Location: ' . SITE_URL . '/painel/mensagens.php?compra_id=' . $compra_id);
    exit;
}

// Marcar mensagens como lidas
db_update('mensagens', ['lida' => 1], 'compra_id = ? AND remetente_id != ? AND lida = 0', [$compra_id, $outra_parte_id]);

// Buscar mensagens
$mensagens = db_fetch_all(
    "SELECT m.*, u.nome as remetente_nome, u.admin as remetente_admin
     FROM mensagens m
     JOIN usuarios u ON m.remetente_id = u.id
     WHERE m.compra_id = ?
     ORDER BY m.criado_em ASC",
    [$compra_id]
);

$status_labels = [
    'aguardando_pagamento' => ['Aguardando Pagamento', 'var(--neon-yellow)', 'fas fa-clock'],
    'pagamento_confirmado' => ['Pagamento Confirmado', 'var(--neon-blue)', 'fas fa-check'],
    'em_entrega' => ['Em Entrega', 'var(--neon-purple)', 'fas fa-truck'],
    'concluido' => ['Concluido', 'var(--neon-green)', 'fas fa-check-circle'],
    'cancelado' => ['Cancelado', 'var(--text-muted)', 'fas fa-ban'],
    'disputa' => ['Em Disputa', 'var(--neon-pink)', 'fas fa-gavel'],
];
$sl = $status_labels[$compra['status']] ?? ['Desconhecido', 'var(--text-muted)', 'fas fa-question'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 20px; padding-bottom: 20px; max-width: 800px; height: calc(100vh - 100px); display: flex; flex-direction: column;">

    <!-- Header do Chat -->
    <div class="card" style="padding: 15px 20px; margin-bottom: 15px; flex-shrink: 0;">
        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
            <div>
                <a href="<?php echo $is_comprador ? '/painel/compras.php' : '/painel/vendas.php'; ?>" style="color: var(--neon-green); text-decoration: none; font-size: 0.85rem;">
                    <i class="fas fa-arrow-left"></i> Voltar
                </a>
                <h2 style="margin: 5px 0 0 0; color: var(--text); font-size: 1rem;">
                    Compra #<?php echo $compra_id; ?> — <?php echo sanitize($compra['anuncio_titulo']); ?>
                </h2>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin: 0;">
                    Conversa com: <strong><?php echo sanitize($outra_parte); ?></strong>
                </p>
            </div>
            <span style="color: <?php echo $sl[1]; ?>; padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; background: rgba(0,0,0,0.2);">
                <i class="<?php echo $sl[2]; ?>"></i> <?php echo $sl[0]; ?>
            </span>
        </div>
    </div>

    <!-- Mensagens -->
    <div id="chat-messages" class="card" style="flex: 1; overflow-y: auto; padding: 15px; margin-bottom: 15px; display: flex; flex-direction: column; gap: 10px;">
        <?php if (empty($mensagens)): ?>
            <div style="text-align: center; padding: 30px; color: var(--text-muted);">
                <i class="fas fa-comments" style="font-size: 2rem; margin-bottom: 10px; display: block;"></i>
                <p>Nenhuma mensagem ainda. Inicie a conversa!</p>
            </div>
        <?php else: ?>
            <?php foreach ($mensagens as $m): ?>
            <?php $is_me = ($m['remetente_id'] == $user_id); ?>
            <div style="display: flex; justify-content: <?php echo $is_me ? 'flex-end' : 'flex-start'; ?>;">
                <div style="max-width: 75%; padding: 10px 15px; border-radius: 12px; <?php echo $is_me ? 'background: var(--neon-green); color: #000; border-bottom-right-radius: 4px;' : 'background: var(--bg-input); color: var(--text); border-bottom-left-radius: 4px;'; ?>">
                    <?php if (!$is_me): ?>
                    <div style="font-size: 0.75rem; font-weight: 600; margin-bottom: 3px; color: <?php echo $m['remetente_admin'] ? 'var(--neon-blue)' : 'var(--neon-green)'; ?>;">
                        <?php if ($m['remetente_admin']): ?><i class="fas fa-shield-alt"></i> <?php endif; ?>
                        <?php echo sanitize($m['remetente_nome']); ?>
                    </div>
                    <?php endif; ?>
                    <p style="margin: 0; line-height: 1.5; white-space: pre-wrap; word-break: break-word; font-size: 0.9rem;">
                        <?php echo nl2br(sanitize($m['conteudo'])); ?>
                    </p>
                    <div style="font-size: 0.7rem; margin-top: 4px; text-align: right; opacity: 0.7;">
                        <?php echo date('H:i', strtotime($m['criado_em'])); ?>
                        <?php if ($is_me): ?>
                            <i class="fas fa-<?php echo $m['lida'] ? 'check-double' : 'check'; ?>"></i>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Input de Mensagem -->
    <?php if (!in_array($compra['status'], ['cancelado'])): ?>
    <form method="POST" style="flex-shrink: 0;">
        <?php echo csrf_field(); ?>
        <div style="display: flex; gap: 10px; align-items: flex-end;">
            <textarea name="conteudo" class="form-input" rows="2" required placeholder="Digite sua mensagem..."
                      style="flex: 1; resize: none;" id="msg-input" onkeydown="handleEnter(event)"></textarea>
            <button type="submit" class="btn btn-primary" style="height: 46px; padding: 0 20px;">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </form>
    <?php else: ?>
    <div class="card" style="text-align: center; padding: 15px; flex-shrink: 0; color: var(--text-muted);">
        <i class="fas fa-ban"></i> Esta compra foi cancelada. Chat desabilitado.
    </div>
    <?php endif; ?>
</div>

<script>
// Scroll para baixo
const chat = document.getElementById('chat-messages');
chat.scrollTop = chat.scrollHeight;

// Enter para enviar
function handleEnter(e) {
    if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        e.target.closest('form').submit();
    }
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>