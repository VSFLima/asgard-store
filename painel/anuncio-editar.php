<?php
/**
 * Asgard Store - Editar Anuncio
 */

$page_title = 'Editar Anuncio';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$anuncio_id = intval($_GET['id'] ?? 0);

if ($anuncio_id <= 0) {
    header('Location: ' . SITE_URL . '/painel/anuncios.php');
    exit;
}

// Buscar anuncio (apenas pendente ou reprovado pode ser editado)
$anuncio = db_fetch(
    "SELECT * FROM anuncios WHERE id = ? AND usuario_id = ? AND status IN ('pendente', 'reprovado')",
    [$anuncio_id, $user_id]
);

if (!$anuncio) {
    $_SESSION['flash'] = ['type' => 'error', 'message' => 'Anuncio nao encontrado ou nao pode ser editado.'];
    header('Location: ' . SITE_URL . '/painel/anuncios.php');
    exit;
}

// Buscar jogos ativos
$jogos = db_fetch_all("SELECT * FROM jogos WHERE ativo = 1 ORDER BY ordem ASC, nome ASC");

// Screenshots existentes
$existing_screenshots = json_decode($anuncio['screenshots'] ?? '[]', true) ?: [];
$screenshots_to_keep = $existing_screenshots;

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();

    $titulo = sanitize($_POST['titulo'] ?? '');
    $descricao = sanitize($_POST['descricao'] ?? '');
    $jogo_id = intval($_POST['jogo_id'] ?? 0);
    $nivel_rank = sanitize($_POST['nivel_rank'] ?? '');
    $itens_especiais = sanitize($_POST['itens_especiais'] ?? '');
    $servidor = sanitize($_POST['servidor'] ?? '');
    $preco = floatval($_POST['preco'] ?? 0);
    $video_url = sanitize($_POST['video_url'] ?? '');

    // Screenshots a manter (existentes nao removidas)
    $screenshots_to_keep = [];
    if (!empty($_POST['keep_screenshots'])) {
        foreach ($_POST['keep_screenshots'] as $kept) {
            $kept = basename($kept); // Seguranca
            if (in_array($kept, $existing_screenshots)) {
                $screenshots_to_keep[] = $kept;
            }
        }
    }

    // Validacoes
    if (empty($titulo)) {
        $error = 'Titulo e obrigatorio.';
    } elseif (strlen($titulo) < 5) {
        $error = 'Titulo deve ter pelo menos 5 caracteres.';
    } elseif ($jogo_id <= 0) {
        $error = 'Selecione um jogo.';
    } elseif (empty($descricao)) {
        $error = 'Descricao e obrigatoria.';
    } elseif (strlen($descricao) < 20) {
        $error = 'Descricao deve ter pelo menos 20 caracteres.';
    } elseif ($preco <= 0) {
        $error = 'Preco deve ser maior que zero.';
    } elseif ($preco > 999999.99) {
        $error = 'Preco maximo e R$ 999.999,99.';
    } elseif (count($screenshots_to_keep) > MAX_SCREENSHOTS) {
        $error = 'Maximo de ' . MAX_SCREENSHOTS . ' screenshots.';
    } else {
        // Upload de novas screenshots
        if (!empty($_FILES['screenshots']['name'][0])) {
            $remaining = MAX_SCREENSHOTS - count($screenshots_to_keep);
            $new_files = array_filter($_FILES['screenshots']['name']);
            $total_new = min(count($new_files), $remaining);

            for ($i = 0; $i < $total_new; $i++) {
                $file = [
                    'name' => $_FILES['screenshots']['name'][$i],
                    'type' => $_FILES['screenshots']['type'][$i],
                    'tmp_name' => $_FILES['screenshots']['tmp_name'][$i],
                    'error' => $_FILES['screenshots']['error'][$i],
                    'size' => $_FILES['screenshots']['size'][$i],
                ];
                $uploaded = upload_image($file, UPLOADS_ANUNCIOS);
                if ($uploaded) {
                    $screenshots_to_keep[] = $uploaded;
                }
            }
        }

        if (count($screenshots_to_keep) > MAX_SCREENSHOTS) {
            $error = 'Maximo de ' . MAX_SCREENSHOTS . ' screenshots.';
        }
    }

    if (empty($error)) {
        $data = [
            'jogo_id' => $jogo_id,
            'titulo' => $titulo,
            'descricao' => $descricao,
            'nivel_rank' => $nivel_rank,
            'itens_especiais' => $itens_especiais,
            'servidor' => $servidor,
            'screenshots' => !empty($screenshots_to_keep) ? json_encode($screenshots_to_keep) : null,
            'video_url' => $video_url,
            'preco' => $preco,
            'status' => 'pendente', // Volta para pendente apos edicao
            'motivo_reprovacao' => null,
        ];

        $updated = db_update('anuncios', $data, 'id = ?', [$anuncio_id]);

        if ($updated) {
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anuncio atualizado com sucesso! Aguardando nova aprovacao.'];
            header('Location: ' . SITE_URL . '/painel/anuncios.php');
            exit;
        } else {
            $error = 'Erro ao atualizar anuncio.';
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px; max-width: 800px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-edit" style="color: var(--neon-green);"></i> Editar Anuncio
    </h1>

    <?php if ($anuncio['status'] === 'reprovado' && !empty($anuncio['motivo_reprovacao'])): ?>
        <div class="alert alert-error" style="margin-bottom: 20px;">
            <i class="fas fa-exclamation-triangle"></i>
            <strong>Motivo da Reprovacao:</strong> <?php echo sanitize($anuncio['motivo_reprovacao']); ?>
        </div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="/painel/anuncio-editar.php?id=<?php echo $anuncio_id; ?>" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <!-- Jogo e Titulo -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-gamepad" style="color: var(--neon-green);"></i> Informacoes do Anuncio</h3>
            <div class="form-group">
                <label class="form-label">Jogo *</label>
                <select name="jogo_id" class="form-select" required>
                    <option value="">Selecione o jogo</option>
                    <?php foreach ($jogos as $jogo): ?>
                        <option value="<?php echo $jogo['id']; ?>" <?php echo ($anuncio['jogo_id'] == $jogo['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($jogo['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Titulo do Anuncio *</label>
                <input type="text" name="titulo" class="form-input" required maxlength="255"
                       value="<?php echo sanitize($anuncio['titulo']); ?>">
            </div>
            <div class="form-group">
                <label class="form-label">Descricao *</label>
                <textarea name="descricao" class="form-input" rows="6" required><?php echo sanitize($anuncio['descricao']); ?></textarea>
            </div>
        </div>

        <!-- Detalhes da Conta -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-info-circle" style="color: var(--neon-green);"></i> Detalhes da Conta</h3>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Nivel / Rank</label>
                    <input type="text" name="nivel_rank" class="form-input" value="<?php echo sanitize($anuncio['nivel_rank'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Servidor</label>
                    <input type="text" name="servidor" class="form-input" value="<?php echo sanitize($anuncio['servidor'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Itens Especiais</label>
                <textarea name="itens_especiais" class="form-input" rows="3"><?php echo sanitize($anuncio['itens_especiais'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Screenshots Existentes + Upload -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-camera" style="color: var(--neon-green);"></i> Screenshots</h3>

            <?php if (!empty($existing_screenshots)): ?>
                <div style="margin-bottom: 15px;">
                    <label class="form-label">Screenshots Atuais (desmarque para remover)</label>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <?php foreach ($existing_screenshots as $idx => $screenshot): ?>
                            <label style="position:relative;display:block;width:120px;height:90px;border-radius:8px;overflow:hidden;border:2px solid var(--border-color);cursor:pointer;">
                                <input type="checkbox" name="keep_screenshots[]" value="<?php echo $screenshot; ?>"
                                       checked style="position:absolute;top:5px;right:5px;z-index:2;width:auto;">
                                <img src="/assets/img/uploads/anuncios/<?php echo $screenshot; ?>"
                                     style="width:100%;height:100%;object-fit:cover;" alt="Screenshot">
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label class="form-label">Adicionar Novas Screenshots (maximo <?php echo MAX_SCREENSHOTS; ?> total)</label>
                <input type="file" name="screenshots[]" class="form-input" accept="image/*" multiple
                       id="screenshots-input" <?php echo count($existing_screenshots) >= MAX_SCREENSHOTS ? 'disabled' : ''; ?>>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;">
                    Restam <?php echo MAX_SCREENSHOTS - count($existing_screenshots); ?> slots.
                </p>
            </div>
            <div id="screenshots-preview" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;"></div>
        </div>

        <!-- Video -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-video" style="color: var(--neon-green);"></i> Video (Opcional)</h3>
            <div class="form-group">
                <label class="form-label">URL do Video</label>
                <input type="url" name="video_url" class="form-input" placeholder="https://youtube.com/watch?v=..."
                       value="<?php echo sanitize($anuncio['video_url'] ?? ''); ?>">
            </div>
        </div>

        <!-- Preco -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-tag" style="color: var(--neon-green);"></i> Preco</h3>
            <div class="form-group">
                <label class="form-label">Preco (R$) *</label>
                <input type="number" name="preco" class="form-input" required step="0.01" min="0.01" max="999999.99"
                       value="<?php echo number_format($anuncio['preco'], 2, '.', ''); ?>">
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;">
                    Comissao: <?php echo COMISSAO_PADRAO; ?>%. Voce recebe: R$ <span id="recebe-valor">
                        <?php echo number_format($anuncio['preco'] - ($anuncio['preco'] * COMISSAO_PADRAO / 100), 2, ',', '.'); ?>
                    </span>
                </p>
            </div>
        </div>

        <div style="display: flex; gap: 15px; align-items: center;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-save"></i> Salvar Alteracoes
            </button>
            <a href="/painel/anuncios.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </form>
</div>

<script>
// Preview de screenshots
document.getElementById('screenshots-input')?.addEventListener('change', function(e) {
    const preview = document.getElementById('screenshots-preview');
    preview.innerHTML = '';
    const files = Array.from(e.target.files).slice(0, <?php echo MAX_SCREENSHOTS; ?>);
    files.forEach(function(file) {
        const reader = new FileReader();
        reader.onload = function(ev) {
            const div = document.createElement('div');
            div.style.cssText = 'position:relative;width:120px;height:90px;border-radius:8px;overflow:hidden;border:2px solid var(--border-color);';
            div.innerHTML = '<img src="' + ev.target.result + '" style="width:100%;height:100%;object-fit:cover;">';
            preview.appendChild(div);
        };
        reader.readAsDataURL(file);
    });
});

// Calcular valor recebido
document.querySelector('input[name="preco"]').addEventListener('input', function() {
    const preco = parseFloat(this.value) || 0;
    const comissao = <?php echo COMISSAO_PADRAO; ?>;
    const recebe = preco - (preco * comissao / 100);
    document.getElementById('recebe-valor').textContent = recebe.toLocaleString('pt-BR', {minimumFractionDigits: 2});
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>