<?php
/**
 * Asgard Store - Contato
 */

$page_title = 'Contato';
require_once __DIR__ . '/../includes/functions.php';

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $nome = sanitize($_POST['nome'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $assunto = sanitize($_POST['assunto'] ?? '');
    $mensagem = sanitize($_POST['mensagem'] ?? '');

    if (empty($nome) || empty($email) || empty($assunto) || empty($mensagem)) {
        $error = 'Preencha todos os campos.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Email invalido.';
    } elseif (strlen($mensagem) < 10) {
        $error = 'Mensagem deve ter pelo menos 10 caracteres.';
    } else {
        // Se logado, criar ticket de suporte
        if (is_logged_in()) {
            $ticket_id = db_insert('suporte_tickets', [
                'usuario_id' => $_SESSION['user_id'],
                'assunto' => "[Contato] {$assunto}",
                'mensagem' => "Nome: {$nome}\nEmail: {$email}\n\n{$mensagem}",
                'status' => 'aberto',
                'prioridade' => 'normal',
            ]);

            // Notificar admins
            $admins = db_fetch_all("SELECT id FROM usuarios WHERE admin = 1");
            foreach ($admins as $admin) {
                create_notification($admin['id'], 'Mensagem de Contato', "Nova mensagem de {$nome}: {$assunto}", 'suporte', "/suporte/ticket.php?id={$ticket_id}");
            }
        }

        $success = 'Mensagem enviada com sucesso! Entraremos em contato em breve.';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 40px; padding-bottom: 60px; max-width: 800px;">
    <h1 class="section-title" style="text-align: center; margin-bottom: 10px;">
        <i class="fas fa-envelope" style="color: var(--neon-green);"></i> Contato
    </h1>
    <p style="text-align: center; color: var(--text-muted); margin-bottom: 40px;">
        Fale conosco. Estamos prontos para ajudar!
    </p>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <!-- Formulario -->
        <div class="card" style="padding: 25px; grid-column: <?php echo is_logged_in() ? '1 / -1' : '1'; ?>;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-paper-plane" style="color: var(--neon-green);"></i> Enviar Mensagem</h3>

            <form method="POST" action="/pages/contato.php">
                <?php echo csrf_field(); ?>

                <div class="form-row-2">
                    <div class="form-group">
                        <label class="form-label">Nome *</label>
                        <input type="text" name="nome" class="form-input" required
                               value="<?php echo is_logged_in() ? sanitize(current_user()['nome']) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email *</label>
                        <input type="email" name="email" class="form-input" required
                               value="<?php echo is_logged_in() ? sanitize(current_user()['email']) : ''; ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Assunto *</label>
                    <select name="assunto" class="form-select" required>
                        <option value="">Selecione o assunto</option>
                        <option value="Duvida sobre compra">Duvida sobre compra</option>
                        <option value="Duvida sobre venda">Duvida sobre venda</option>
                        <option value="Problema com pagamento">Problema com pagamento</option>
                        <option value="Reportar bug">Reportar bug</option>
                        <option value="Sugestao">Sugestao</option>
                        <option value="Parceria">Parceria</option>
                        <option value="Outro">Outro</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Mensagem *</label>
                    <textarea name="mensagem" class="form-input" rows="6" required
                              placeholder="Descreva sua mensagem em detalhes..."></textarea>
                </div>

                <button type="submit" class="btn btn-primary btn-lg">
                    <i class="fas fa-paper-plane"></i> Enviar
                </button>
            </form>
        </div>

        <!-- Info de Contato (so aparece se NAO estiver logado) -->
        <?php if (!is_logged_in()): ?>
        <div>
            <div class="card" style="padding: 25px; margin-bottom: 20px;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-headset" style="color: var(--neon-green);"></i> Suporte</h3>
                <p style="color: var(--text-muted); margin-bottom: 15px;">Para duvidas e problemas, use nosso sistema de tickets.</p>
                <a href="/suporte/" class="btn btn-primary" style="width: 100%;">
                    <i class="fas fa-ticket-alt"></i> Abrir Ticket
                </a>
            </div>

            <div class="card" style="padding: 25px; margin-bottom: 20px;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-question-circle" style="color: var(--neon-yellow);"></i> FAQ</h3>
                <p style="color: var(--text-muted); margin-bottom: 15px;">Consulte nossas perguntas frequentes.</p>
                <a href="/pages/faq.php" class="btn btn-secondary" style="width: 100%;">
                    <i class="fas fa-book"></i> Ver FAQ
                </a>
            </div>

            <div class="card" style="padding: 25px;">
                <h3 style="margin-bottom: 15px;"><i class="fas fa-clock" style="color: var(--neon-blue);"></i> Horario</h3>
                <p style="color: var(--text-muted); line-height: 1.6;">
                    Segunda a Sexta: 9h - 18h<br>
                    Sabado: 9h - 14h<br>
                    Domingo: Apenas tickets urgentes
                </p>
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-top: 10px;">
                    <i class="fas fa-clock"></i> Tempo medio de resposta: 2-4 horas
                </p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>