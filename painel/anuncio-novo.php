<?php
/**
 * Asgard Store - Criar Novo Anuncio
 */

$page_title = 'Criar Anuncio';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];

// Buscar jogos ativos
$jogos = db_fetch_all("SELECT * FROM jogos WHERE ativo = 1 ORDER BY ordem ASC, nome ASC");

// Buscar categorias
$all_categorias = db_fetch_all("SELECT * FROM categorias ORDER BY jogo_id, nome");

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
    } else {
        // Upload de screenshots
        $screenshots = [];
        if (!empty($_FILES['screenshots']['name'][0])) {
            $total = count($_FILES['screenshots']['name']);
            if ($total > MAX_SCREENSHOTS) {
                $error = 'Maximo de ' . MAX_SCREENSHOTS . ' screenshots.';
            } else {
                for ($i = 0; $i < $total; $i++) {
                    $file = [
                        'name' => $_FILES['screenshots']['name'][$i],
                        'type' => $_FILES['screenshots']['type'][$i],
                        'tmp_name' => $_FILES['screenshots']['tmp_name'][$i],
                        'error' => $_FILES['screenshots']['error'][$i],
                        'size' => $_FILES['screenshots']['size'][$i],
                    ];
                    $uploaded = upload_image($file, UPLOADS_ANUNCIOS);
                    if ($uploaded) {
                        $screenshots[] = $uploaded;
                    }
                }
            }
        }

        if (empty($error)) {
            $data = [
                'usuario_id' => $user_id,
                'jogo_id' => $jogo_id,
                'titulo' => $titulo,
                'descricao' => $descricao,
                'nivel_rank' => $nivel_rank,
                'itens_especiais' => $itens_especiais,
                'servidor' => $servidor,
                'screenshots' => !empty($screenshots) ? json_encode($screenshots) : null,
                'video_url' => $video_url,
                'preco' => $preco,
                'status' => 'pendente',
            ];

            $anuncio_id = db_insert('anuncios', $data);

            if ($anuncio_id) {
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Anuncio criado com sucesso! Aguardando aprovacao.'];
                header('Location: ' . SITE_URL . '/painel/anuncios.php');
                exit;
            } else {
                $error = 'Erro ao criar anuncio. Tente novamente.';
            }
        }
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px; max-width: 800px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-plus-circle" style="color: var(--neon-green);"></i> Criar Novo Anuncio
    </h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <form method="POST" action="/painel/anuncio-novo.php" enctype="multipart/form-data">
        <?php echo csrf_field(); ?>

        <!-- Jogo e Titulo -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-gamepad" style="color: var(--neon-green);"></i> Informacoes do Anuncio</h3>
            <div class="form-group">
                <label class="form-label">Jogo *</label>
                <select name="jogo_id" class="form-select" required>
                    <option value="">Selecione o jogo</option>
                    <?php foreach ($jogos as $jogo): ?>
                        <option value="<?php echo $jogo['id']; ?>" <?php echo (intval($_POST['jogo_id'] ?? 0) === $jogo['id']) ? 'selected' : ''; ?>>
                            <?php echo sanitize($jogo['nome']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Titulo do Anuncio *</label>
                <input type="text" name="titulo" class="form-input" required maxlength="255"
                       placeholder="Ex: Conta Nivel 100, Todos os Itens Desbloqueados"
                       value="<?php echo sanitize($_POST['titulo'] ?? ''); ?>">
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;">Minimo 5 caracteres</p>
            </div>
            <div class="form-group">
                <label class="form-label">Descricao *</label>
                <textarea name="descricao" class="form-input" rows="6" required
                          placeholder="Descreva sua conta em detalhes: nivel, itens, progresso, etc."><?php echo sanitize($_POST['descricao'] ?? ''); ?></textarea>
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;">Minimo 20 caracteres</p>
            </div>
        </div>

        <!-- Detalhes da Conta -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-info-circle" style="color: var(--neon-green);"></i> Detalhes da Conta</h3>
            <div class="form-row-2">
                <div class="form-group">
                    <label class="form-label">Nivel / Rank</label>
                    <input type="text" name="nivel_rank" class="form-input" placeholder="Ex: Nivel 100, Rank 5 estrelas"
                           value="<?php echo sanitize($_POST['nivel_rank'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label class="form-label">Servidor</label>
                    <input type="text" name="servidor" class="form-input" placeholder="Ex: BR1, Global, Asia"
                           value="<?php echo sanitize($_POST['servidor'] ?? ''); ?>">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Itens Especiais</label>
                <textarea name="itens_especiais" class="form-input" rows="3"
                          placeholder="Liste itens raros, skin exclusivas, etc."><?php echo sanitize($_POST['itens_especiais'] ?? ''); ?></textarea>
            </div>
        </div>

        <!-- Screenshots -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-camera" style="color: var(--neon-green);"></i> Screenshots</h3>
            <div class="form-group">
                <label class="form-label">Fotos da Conta (maximo <?php echo MAX_SCREENSHOTS; ?>)</label>
                <input type="file" name="screenshots[]" class="form-input" accept="image/*" multiple
                       id="screenshots-input">
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;">
                    Formatos aceitos: JPG, PNG, GIF, WebP. Maximo 5MB cada.
                </p>
            </div>
            <div id="screenshots-preview" style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 15px;"></div>
        </div>

        <!-- Video -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-video" style="color: var(--neon-green);"></i> Video (Opcional)</h3>
            <div class="form-group">
                <label class="form-label">URL do Video</label>
                <input type="url" name="video_url" class="form-input" placeholder="https://youtube.com/watch?v=..."
                       value="<?php echo sanitize($_POST['video_url'] ?? ''); ?>">
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;">YouTube, TikTok ou outro link de video</p>
            </div>
        </div>

        <!-- Preco -->
        <div class="card" style="margin-bottom: 20px;">
            <h3 style="margin-bottom: 20px;"><i class="fas fa-tag" style="color: var(--neon-green);"></i> Preco</h3>
            <div class="form-group">
                <label class="form-label">Preco (R$) *</label>
                <input type="number" name="preco" class="form-input" required step="0.01" min="0.01" max="999999.99"
                       placeholder="0.00" value="<?php echo sanitize($_POST['preco'] ?? ''); ?>">
                <p style="color: var(--text-muted); font-size: 0.8rem; margin-top: 5px;">
                    Comissao da plataforma: <?php echo COMISSAO_PADRAO; ?>%. Voce recebe: R$ <span id="recebe-valor">0,00</span>
                </p>
            </div>
        </div>

        <div style="display: flex; gap: 15px; align-items: center;">
            <button type="submit" class="btn btn-primary btn-lg">
                <i class="fas fa-paper-plane"></i> Criar Anuncio
            </button>
            <a href="/painel/anuncios.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Voltar
            </a>
        </div>
    </form>
</div>

<script>
// Preview de screenshots
document.getElementById('screenshots-input').addEventListener('change', function(e) {
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