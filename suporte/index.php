<?php
/**
 * Asgard Store - Abrir Ticket de Suporte
 */

$page_title = 'Suporte';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $assunto = sanitize($_POST['assunto'] ?? '');
    $mensagem = sanitize($_POST['mensagem'] ?? '');
    $prioridade = $_POST['prioridade'] ?? 'normal';

    if (empty($assunto)) {
        $error = 'Assunto e obrigatorio.';
    } elseif (strlen($assunto) < 5) {
        $error = 'Assunto deve ter pelo menos 5 caracteres.';
    } elseif (empty($mensagem)) {
        $error = 'Mensagem e obrigatoria.';
    } elseif (strlen($mensagem) < 10) {
        $error = 'Mensagem deve ter pelo menos 10 caracteres.';
    } elseif (!in_array($prioridade, ['baixa', 'normal', 'alta', 'urgente'])) {
        $prioridade = 'normal';
    } else {
        // Verificar limite de tickets abertos
        $abertos = db_count('suporte_tickets', 'usuario_id = ? AND status != ?', [$user_id, 'resolvido']);
        if ($abertos >= 5) {
            $error = 'Voce ja tem muitos tickets abertos. Aguarde resposta ou feche os anteriores.';
        } else {
            $ticket_id = db_insert('suporte_tickets', [
                'usuario_id' => $user_id,
                'assunto' => $assunto,
                'mensagem' => $mensagem,
                'status' => 'aberto',
                'prioridade' => $prioridade,
            ]);

            if ($ticket_id) {
                // Notificar admins
                $admins = db_fetch_all("SELECT id FROM usuarios WHERE admin = 1");
                foreach ($admins as $admin) {
                    create_notification($admin['id'], 'Novo Ticket de Suporte', "Ticket #{$ticket_id}: {$assunto}", 'suporte', '/admin/suporte.php');
                }

                $_SESSION['flash'] = ['type' => 'success', 'message' => "Ticket #{$ticket_id} criado com sucesso!"];
                header('Location: ' . SITE_URL . '/suporte/ticket.php?id=' . $ticket_id);
                exit;
            } else {
                $error = 'Erro ao criar ticket. Tente novamente.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px; max-width: 700px;">
    <h1 class="section-title" style="margin-bottom: 10px;">
        <i class="fas fa-headset" style="color: var(--neon-green);"></i> Suporte
    </h1>
    <p style="color: var(--text-muted); margin-bottom: 30px;">Abra um ticket e nossa equipe respondera em breve.</p>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Links uteis -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 25px;">
        <a href="/suporte/listar.php" class="card" style="text-decoration: none; padding: 15px; text-align: center; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-list" style="color: var(--neon-blue);"></i>
            <span style="color: var(--text);">Meus Tickets</span>
        </a>
        <a href="/pages/faq.php" class="card" style="text-decoration: none; padding: 15px; text-align: center; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-question-circle" style="color: var(--neon-yellow);"></i>
            <span style="color: var(--text);">FAQ</span>
        </a>
        <a href="/pages/contato.php" class="card" style="text-decoration: none; padding: 15px; text-align: center; display: flex; align-items: center; gap: 10px;">
            <i class="fas fa-envelope" style="color: var(--neon-green);"></i>
            <span style="color: var(--text);">Contato</span>
        </a>
    </div>

    <!-- Formulario -->
    <div class="card" style="padding: 25px;">
        <h3 style="margin-bottom: 20px;"><i class="fas fa-plus-circle" style="color: var(--neon-green);"></i> Novo Ticket</h3>

        <form method="POST" action="/suporte/">
            <?php echo csrf_field(); ?>

            <div class="form-group">
                <label class="form-label">Assunto *</label>
                <input type="text" name="assunto" class="form-input" required maxlength="200"
                       placeholder="Ex: Problema com pagamento, Duvia sobre anuncio..."
                       value="<?php echo sanitize($_POST['assunto'] ?? ''); ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Prioridade</label>
                <select name="prioridade" class="form-select">
                    <option value="baixa">🟢 Baixa</option>
                    <option value="normal" selected>🟡 Normal</option>
                    <option value="alta">🟠 Alta</option>
                    <option value="urgente">🔴 Urgente</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Mensagem *</label>
                <textarea name="mensagem" class="form-input" rows="6" required
                          placeholder="Descreva seu problema ou duvida em detalhes..."><?php echo sanitize($_POST['mensagem'] ?? ''); ?></textarea>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;">Minimo 10 caracteres</p>
            </div>

            <div style="display: flex; gap: 15px; align-items: center;">
                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Enviar Ticket
                </button>
                <a href="/suporte/listar.php" class="btn btn-secondary">Meus Tickets</a>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>