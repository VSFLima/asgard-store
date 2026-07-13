<?php
/**
 * Asgard Store - Ver Ticket de Suporte
 */

$page_title = 'Ticket';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$is_admin = is_admin();
$ticket_id = intval($_GET['id'] ?? 0);

if ($ticket_id <= 0) {
    header('Location: ' . SITE_URL . '/suporte/listar.php');
    exit;
}

// Buscar ticket
$ticket = db_fetch(
    "SELECT t.*, u.nome as usuario_nome 
     FROM suporte_tickets t 
     JOIN usuarios u ON t.usuario_id = u.id 
     WHERE t.id = ?",
    [$ticket_id]
);

if (!$ticket) {
    $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Ticket nao encontrado.'];
    header('Location: ' . SITE_URL . '/suporte/listar.php');
    exit;
}

// Verificar permissao: proprio usuario ou admin
if ($ticket['usuario_id'] != $user_id && !$is_admin) {
    $_SESSION['flash'] = ['tipo' => 'error', 'msg' => 'Acesso negado.'];
    header('Location: ' . SITE_URL . '/suporte/listar.php');
    exit;
}

// Enviar resposta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $acao = $_POST['acao'] ?? '';

    if ($acao === 'responder') {
        $mensagem = sanitize($_POST['mensagem'] ?? '');
        if (!empty($mensagem) && strlen($mensagem) >= 2) {
            db_insert('suporte_respostas', [
                'ticket_id' => $ticket_id,
                'usuario_id' => $user_id,
                'mensagem' => $mensagem,
            ]);

            // Atualizar status
            if ($ticket['status'] === 'resolvido') {
                db_update('suporte_tickets', ['status' => 'aberto'], 'id = ?', [$ticket_id]);
            }

            // Notificar
            if ($is_admin) {
                create_notification($ticket['usuario_id'], 'Resposta no Ticket', "O suporte respondeu ao ticket #{$ticket_id}", 'suporte', "/suporte/ticket.php?id={$ticket_id}");
            } else {
                // Notificar admins
                $admins = db_fetch_all("SELECT id FROM usuarios WHERE admin = 1");
                foreach ($admins as $admin) {
                    create_notification($admin['id'], 'Resposta no Ticket', "Usuario respondeu ao ticket #{$ticket_id}", 'suporte', "/suporte/ticket.php?id={$ticket_id}");
                }
            }
        }
    } elseif ($acao === 'fechar' && $ticket['usuario_id'] == $user_id) {
        db_update('suporte_tickets', ['status' => 'resolvido'], 'id = ?', [$ticket_id]);
        $_SESSION['flash'] = ['tipo' => 'success', 'msg' => 'Ticket fechado.'];
    } elseif ($acao === 'status' && $is_admin) {
        $novo_status = $_POST['novo_status'] ?? '';
        if (in_array($novo_status, ['aberto', 'em_andamento', 'resolvido'])) {
            db_update('suporte_tickets', ['status' => $novo_status], 'id = ?', [$ticket_id]);
        }
    }

    header('Location: ' . SITE_URL . '/suporte/ticket.php?id=' . $ticket_id);
    exit;
}

// Buscar respostas
$respostas = db_fetch_all(
    "SELECT r.*, u.nome as autor_nome, u.admin as autor_admin
     FROM suporte_respostas r
     JOIN usuarios u ON r.usuario_id = u.id
     WHERE r.ticket_id = ?
     ORDER BY r.criado_em ASC",
    [$ticket_id]
);

$status_styles = [
    'aberto' => ['color: var(--neon-yellow); background: rgba(255,193,7,0.1);', 'Aberto'],
    'em_andamento' => ['color: var(--neon-blue); background: rgba(0,123,255,0.1);', 'Em Andamento'],
    'resolvido' => ['color: var(--neon-green); background: rgba(0,255,136,0.1);', 'Resolvido'],
];
$prioridade_styles = [
    'baixa' => 'color: var(--neon-green);',
    'normal' => 'color: var(--neon-yellow);',
    'alta' => 'color: #ff9800;',
    'urgente' => 'color: var(--neon-pink);',
];
$ss = $status_styles[$ticket['status']] ?? ['', ucfirst($ticket['status'])];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px; max-width: 800px;">
    <!-- Header do Ticket -->
    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px; margin-bottom: 20px;">
        <div>
            <a href="/suporte/listar.php" style="color: var(--neon-green); text-decoration: none; font-size: 0.85rem;">
                <i class="fas fa-arrow-left"></i> Voltar aos tickets
            </a>
            <h1 style="margin-top: 10px; color: var(--text); font-size: 1.3rem;">
                Ticket #<?php echo $ticket['id']; ?> — <?php echo sanitize($ticket['assunto']); ?>
            </h1>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <span style="<?php echo $ss[0]; ?> padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                <?php echo $ss[1]; ?>
            </span>
            <span style="<?php echo $prioridade_styles[$ticket['prioridade']] ?? ''; ?> font-size: 0.85rem;">
                <i class="fas fa-flag"></i> <?php echo ucfirst($ticket['prioridade']); ?>
            </span>
        </div>
    </div>

    <!-- Info do Ticket -->
    <div class="card" style="margin-bottom: 20px; padding: 15px;">
        <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: 0.85rem; color: var(--text-muted);">
            <span><i class="fas fa-user"></i> <?php echo sanitize($ticket['usuario_nome']); ?></span>
            <span><i class="fas fa-clock"></i> Criado: <?php echo format_date($ticket['criado_em']); ?></span>
            <span><i class="fas fa-comments"></i> <?php echo count($respostas); ?> resposta(s)</span>
        </div>
    </div>

    <!-- Mensagem Original -->
    <div class="card" style="margin-bottom: 15px; border-left: 3px solid var(--neon-blue);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong style="color: var(--neon-blue);"><i class="fas fa-user-circle"></i> <?php echo sanitize($ticket['usuario_nome']); ?></strong>
            <span style="color: var(--text-muted); font-size: 0.8rem;"><?php echo format_date($ticket['criado_em']); ?></span>
        </div>
        <div style="color: var(--text); line-height: 1.6; white-space: pre-wrap;"><?php echo nl2br(sanitize($ticket['mensagem'])); ?></div>
    </div>

    <!-- Respostas -->
    <?php foreach ($respostas as $r): ?>
    <div class="card" style="margin-bottom: 15px; border-left: 3px solid <?php echo $r['autor_admin'] ? 'var(--neon-green)' : 'var(--neon-blue)'; ?>;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
            <strong style="color: <?php echo $r['autor_admin'] ? 'var(--neon-green)' : 'var(--neon-blue)'; ?>;">
                <?php if ($r['autor_admin']): ?><i class="fas fa-shield-alt"></i> <?php else: ?><i class="fas fa-user-circle"></i> <?php endif; ?>
                <?php echo sanitize($r['autor_nome']); ?>
                <?php if ($r['autor_admin']): ?>
                    <span style="background: rgba(0,255,136,0.1); color: var(--neon-green); padding: 1px 6px; border-radius: 4px; font-size: 0.7rem; margin-left: 5px;">Suporte</span>
                <?php endif; ?>
            </strong>
            <span style="color: var(--text-muted); font-size: 0.8rem;"><?php echo time_ago($r['criado_em']); ?></span>
        </div>
        <div style="color: var(--text); line-height: 1.6; white-space: pre-wrap;"><?php echo nl2br(sanitize($r['mensagem'])); ?></div>
    </div>
    <?php endforeach; ?>

    <!-- Responder -->
    <?php if ($ticket['status'] !== 'resolvido'): ?>
    <div class="card" style="margin-top: 20px; padding: 20px;">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-reply" style="color: var(--neon-green);"></i> Responder</h3>
        <form method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="acao" value="responder">
            <div class="form-group">
                <textarea name="mensagem" class="form-input" rows="4" required placeholder="Sua mensagem..." minlength="2"></textarea>
            </div>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-paper-plane"></i> Enviar Resposta
                </button>
                <?php if ($ticket['usuario_id'] == $user_id): ?>
                <form method="POST" style="margin: 0;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="acao" value="fechar">
                    <button type="submit" class="btn btn-secondary" onclick="return confirm('Fechar este ticket?')">
                        <i class="fas fa-check-circle"></i> Fechar Ticket
                    </button>
                </form>
                <?php endif; ?>
                <?php if ($is_admin): ?>
                <form method="POST" style="margin: 0; display: flex; gap: 5px; align-items: center;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="acao" value="status">
                    <select name="novo_status" class="form-select" style="width: auto; padding: 6px 10px; font-size: 0.85rem;">
                        <option value="aberto" <?php echo $ticket['status'] === 'aberto' ? 'selected' : ''; ?>>Aberto</option>
                        <option value="em_andamento" <?php echo $ticket['status'] === 'em_andamento' ? 'selected' : ''; ?>>Em Andamento</option>
                        <option value="resolvido" <?php echo $ticket['status'] === 'resolvido' ? 'selected' : ''; ?>>Resolvido</option>
                    </select>
                    <button type="submit" class="btn btn-secondary" style="padding: 6px 12px; font-size: 0.85rem;">Alterar</button>
                </form>
                <?php endif; ?>
            </div>
        </form>
    </div>
    <?php else: ?>
    <div class="card" style="margin-top: 20px; text-align: center; padding: 20px; border-left: 3px solid var(--neon-green);">
        <i class="fas fa-check-circle" style="color: var(--neon-green); font-size: 1.5rem; margin-bottom: 10px;"></i>
        <p style="color: var(--text-muted);">Este ticket foi resolvido.</p>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>