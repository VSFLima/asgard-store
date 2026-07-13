<?php
/**
 * Asgard Store - Alterar Senha
 */

$page_title = 'Alterar Senha';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = db_fetch("SELECT * FROM usuarios WHERE id = ?", [$user_id]);

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $senha_atual = $_POST['senha_atual'] ?? '';
    $nova_senha = $_POST['nova_senha'] ?? '';
    $confirmar_senha = $_POST['confirmar_senha'] ?? '';

    // Validacoes
    if (empty($senha_atual)) {
        $error = 'Informe sua senha atual.';
    } elseif (empty($nova_senha)) {
        $error = 'Informe a nova senha.';
    } elseif (strlen($nova_senha) < 6) {
        $error = 'A nova senha deve ter pelo menos 6 caracteres.';
    } elseif ($nova_senha !== $confirmar_senha) {
        $error = 'As senhas nao conferem.';
    } elseif (!verify_password($senha_atual, $user['senha'])) {
        $error = 'Senha atual incorreta.';
    } elseif ($senha_atual === $nova_senha) {
        $error = 'A nova senha deve ser diferente da atual.';
    } else {
        // Atualizar senha
        $nova_hash = hash_password($nova_senha);
        db_update('usuarios', ['senha' => $nova_hash, 'senha_temporaria' => null], 'id = ?', [$user_id]);

        // Log de auditoria (se for admin logando)
        if ($user['admin']) {
            db_insert('admin_log', [
                'admin_id' => $user_id,
                'acao' => 'alterar_senha',
                'descricao' => 'Senha alterada pelo proprio usuario',
                'tipo' => 'seguranca',
            ]);
        }

        $success = 'Senha alterada com sucesso!';
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px; max-width: 600px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-key" style="color: var(--neon-green);"></i> Alterar Senha
    </h1>

    <?php if ($success): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success; ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="card" style="padding: 25px;">
        <form method="POST" action="/painel/alterar-senha.php">
            <?php echo csrf_field(); ?>

            <!-- Senha Atual -->
            <div class="form-group">
                <label class="form-label">Senha Atual *</label>
                <input type="password" name="senha_atual" class="form-input" required
                       placeholder="Digite sua senha atual" autocomplete="current-password">
            </div>

            <div style="border-top: 1px solid var(--border-color); margin: 20px 0;"></div>

            <!-- Nova Senha -->
            <div class="form-group">
                <label class="form-label">Nova Senha *</label>
                <input type="password" name="nova_senha" class="form-input" required
                       placeholder="Minimo 6 caracteres" autocomplete="new-password" id="nova-senha">
                <div style="margin-top: 8px;">
                    <div id="strength-bar" style="height: 4px; border-radius: 2px; background: var(--bg-input); overflow: hidden;">
                        <div id="strength-fill" style="height: 100%; width: 0%; border-radius: 2px; transition: all 0.3s;"></div>
                    </div>
                    <span id="strength-text" style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px; display: block;"></span>
                </div>
            </div>

            <!-- Confirmar Senha -->
            <div class="form-group">
                <label class="form-label">Confirmar Nova Senha *</label>
                <input type="password" name="confirmar_senha" class="form-input" required
                       placeholder="Repita a nova senha" autocomplete="new-password">
            </div>

            <!-- Requisitos -->
            <div style="background: var(--bg-input); border-radius: 8px; padding: 15px; margin-bottom: 20px;">
                <p style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 8px;">
                    <i class="fas fa-info-circle"></i> Requisitos da senha:
                </p>
                <ul style="color: var(--text-muted); font-size: 0.8rem; padding-left: 20px; margin: 0;">
                    <li id="req-length">Minimo 6 caracteres</li>
                    <li id="req-match">Senhas devem conferir</li>
                </ul>
            </div>

            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Alterar Senha
            </button>
        </form>
    </div>
</div>

<script>
document.getElementById('nova-senha').addEventListener('input', function() {
    const val = this.value;
    const fill = document.getElementById('strength-fill');
    const text = document.getElementById('strength-text');
    const reqLength = document.getElementById('req-length');
    let strength = 0;

    if (val.length >= 6) strength++;
    if (val.length >= 8) strength++;
    if (/[A-Z]/.test(val)) strength++;
    if (/[0-9]/.test(val)) strength++;
    if (/[^A-Za-z0-9]/.test(val)) strength++;

    const levels = [
        { width: '0%', color: 'var(--bg-input)', label: '' },
        { width: '20%', color: 'var(--neon-pink)', label: 'Fraca' },
        { width: '40%', color: '#ff9800', label: 'Media' },
        { width: '60%', color: 'var(--neon-yellow)', label: 'Razoavel' },
        { width: '80%', color: 'var(--neon-blue)', label: 'Forte' },
        { width: '100%', color: 'var(--neon-green)', label: 'Muito Forte' },
    ];

    const level = levels[strength];
    fill.style.width = level.width;
    fill.style.background = level.color;
    text.textContent = level.label;
    text.style.color = level.color;

    reqLength.style.color = val.length >= 6 ? 'var(--neon-green)' : 'var(--text-muted)';
    reqLength.innerHTML = val.length >= 6 
        ? '<i class="fas fa-check" style="color: var(--neon-green);"></i> Minimo 6 caracteres' 
        : '<i class="fas fa-times" style="color: var(--neon-pink);"></i> Minimo 6 caracteres';
});

// Verificar match em tempo real
document.querySelectorAll('input[name="nova_senha"], input[name="confirmar_senha"]').forEach(function(input) {
    input.addEventListener('input', function() {
        const nova = document.querySelector('input[name="nova_senha"]').value;
        const confirmar = document.querySelector('input[name="confirmar_senha"]').value;
        const reqMatch = document.getElementById('req-match');
        
        if (confirmar.length > 0) {
            if (nova === confirmar) {
                reqMatch.style.color = 'var(--neon-green)';
                reqMatch.innerHTML = '<i class="fas fa-check" style="color: var(--neon-green);"></i> Senhas conferem';
            } else {
                reqMatch.style.color = 'var(--neon-pink)';
                reqMatch.innerHTML = '<i class="fas fa-times" style="color: var(--neon-pink);"></i> Senhas nao conferem';
            }
        } else {
            reqMatch.style.color = 'var(--text-muted)';
            reqMatch.innerHTML = 'Senhas devem conferir';
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>