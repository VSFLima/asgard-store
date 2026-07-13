<?php
/**
 * Asgard Store - Admin: Gerenciar Saques
 */

$page_title = 'Gerenciar Saques';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$success = '';
$error = '';

// Processar saque
if ($_SERVER['REQUEST_METHOD'] === 'POST' && in_array($_POST['acao'] ?? '', ['aprovar', 'rejeitar'])) {
    require_csrf();
    $id = intval($_POST['id'] ?? 0);
    $motivo = sanitize($_POST['motivo'] ?? '');

    $saque = db_fetch("SELECT * FROM saques WHERE id = ? AND status IN ('pendente', 'processando')", [$id]);

    if ($saque) {
        if ($_POST['acao'] === 'aprovar') {
            db_update('saques', [
                'status' => 'pago',
                'processado_em' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);

            create_notification($saque['usuario_id'], 'Saque Aprovado', "Seu saque de R$ " . number_format($saque['valor'], 2, ',', '.') . " foi processado!", 'sistema', '/painel/saldo.php');

            db_insert('admin_log', [
                'admin_id' => $_SESSION['user_id'],
                'acao' => 'aprovar_saque',
                'descricao' => "Saque #{$id} aprovado - R$ " . number_format($saque['valor'], 2, ',', '.'),
                'tipo' => 'financeiro',
            ]);
            $success = "Saque #{$id} aprovado com sucesso!";
        } else {
            // Rejeitar e devolver saldo
            db_query("UPDATE usuarios SET saldo = saldo + ? WHERE id = ?", [$saque['valor'], $saque['usuario_id']]);

            db_update('saques', [
                'status' => 'rejeitado',
                'motivo_rejeicao' => $motivo ?: 'Rejeitado pelo admin',
                'processado_em' => date('Y-m-d H:i:s'),
            ], 'id = ?', [$id]);

            create_notification($saque['usuario_id'], 'Saque Rejeitado', "Seu saque de R$ " . number_format($saque['valor'], 2, ',', '.') . " foi rejeitado. Motivo: " . ($motivo ?: 'Sem motivo informado'), 'sistema', '/painel/saldo.php');

            db_insert('admin_log', [
                'admin_id' => $_SESSION['user_id'],
                'acao' => 'rejeitar_saque',
                'descricao' => "Saque #{$id} rejeitado - R$ " . number_format($saque['valor'], 2, ',', '.'),
                'tipo' => 'financeiro',
            ]);
            $success = "Saque #{$id} rejeitado e saldo devolvido.";
        }
    } else {
        $error = 'Saque nao encontrado ou ja processado.';
    }
    header('Location: ' . SITE_URL . '/admin/saques.php');
    exit;
}

// Filtros
$filtro = $_GET['status'] ?? 'pendente';
$page = max(1, intval($_GET['page'] ?? 1));

$where = 's.status = ?';
$params = [$filtro];
if (!in_array($filtro, ['pendente', 'processando', 'pago', 'rejeitado'])) {
    $filtro = 'pendente';
    $params = ['pendente'];
}

$total = db_count('saques s', $where, $params);
$pagination = paginate($total, 20, $page);

$saques = db_fetch_all(
    "SELECT s.*, u.nome, u.email, u.chave_pix, u.tipo_pix
     FROM saques s
     JOIN usuarios u ON s.usuario_id = u.id
     WHERE {$where}
     ORDER BY s.criado_em DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

// Stats
$pendentes = db_count('saques', 'status = ?', ['pendente']);
$processando = db_count('saques', 'status = ?', ['processando']);
$pagos = db_count('saques', 'status = ?', ['pago']);
$valor_pendente = db_fetch("SELECT COALESCE(SUM(valor), 0) as total FROM saques WHERE status = 'pendente'")['total'];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <h1 class="section-title" style="margin-bottom: 30px;">
        <i class="fas fa-money-bill-wave" style="color: var(--neon-green);"></i> Gerenciar Saques
    </h1>

    <?php if ($success): ?>
        <div class="alert alert-success"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
    <?php endif; ?>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;">
        <a href="?status=pendente" class="card" style="text-decoration: none; text-align: center; padding: 15px; border: <?php echo $filtro === 'pendente' ? '2px solid var(--neon-yellow)' : '1px solid var(--border-color)'; ?>;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neon-yellow);"><?php echo $pendentes; ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">Pendentes</div>
        </a>
        <div class="card" style="text-align: center; padding: 15px;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neon-blue);"><?php echo $processando; ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">Processando</div>
        </div>
        <div class="card" style="text-align: center; padding: 15px;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neon-green);"><?php echo $pagos; ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">Pagos</div>
        </div>
        <div class="card" style="text-align: center; padding: 15px;">
            <div style="font-size: 1.5rem; font-weight: 700; color: var(--neon-yellow);">R$ <?php echo number_format($valor_pendente, 2, ',', '.'); ?></div>
            <div style="color: var(--text-muted); font-size: 0.8rem;">Valor Pendente</div>
        </div>
    </div>

    <!-- Filtros -->
    <div style="display: flex; gap: 8px; margin-bottom: 20px; flex-wrap: wrap;">
        <?php foreach (['pendente', 'processando', 'pago', 'rejeitado'] as $f): ?>
            <a href="?status=<?php echo $f; ?>" class="btn <?php echo $filtro === $f ? 'btn-primary' : 'btn-secondary'; ?>" style="font-size: 0.85rem;">
                <?php echo ucfirst($f); ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Lista -->
    <?php if (empty($saques)): ?>
        <div class="card" style="text-align: center; padding: 40px;">
            <i class="fas fa-check-circle" style="font-size: 3rem; color: var(--neon-green); margin-bottom: 15px; display: block;"></i>
            <p style="color: var(--text-muted);">Nenhum saque <?php echo $filtro; ?>.</p>
        </div>
    <?php else: ?>
        <?php foreach ($saques as $s): ?>
        <div class="card" style="margin-bottom: 15px; <?php echo $s['status'] === 'pendente' ? 'border-left: 3px solid var(--neon-yellow);' : ''; ?>">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 15px;">
                <div style="flex: 1; min-width: 200px;">
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 8px;">
                        <strong style="color: var(--text); font-size: 1.1rem;">R$ <?php echo number_format($s['valor'], 2, ',', '.'); ?></strong>
                        <?php
                        $status_styles = [
                            'pendente' => 'background: rgba(255,193,7,0.1); color: var(--neon-yellow);',
                            'processando' => 'background: rgba(0,123,255,0.1); color: var(--neon-blue);',
                            'pago' => 'background: rgba(0,255,136,0.1); color: var(--neon-green);',
                            'rejeitado' => 'background: rgba(255,107,107,0.1); color: var(--neon-pink);',
                        ];
                        ?>
                        <span style="<?php echo $status_styles[$s['status']] ?? ''; ?> padding: 3px 10px; border-radius: 12px; font-size: 0.8rem; font-weight: 600;">
                            <?php echo ucfirst($s['status']); ?>
                        </span>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 5px;">
                        <i class="fas fa-user"></i> <?php echo sanitize($s['nome']); ?> (<?php echo sanitize($s['email']); ?>)
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.85rem; margin-bottom: 5px;">
                        <i class="fas fa-wallet"></i> <?php echo ucfirst($s['metodo']); ?>:
                        <?php echo $s['metodo'] === 'pix' ? sanitize($s['chave_pix']) : sanitize($s['wallet_crypto']); ?>
                    </div>
                    <?php if ($s['metodo'] === 'pix'): ?>
                    <div style="color: var(--text-muted); font-size: 0.8rem;">
                        Tipo PIX: <?php echo ucfirst($s['tipo_pix'] ?? 'N/A'); ?>
                        (Chave perfil: <?php echo sanitize($s['chave_pix']); ?>)
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($s['motivo_rejeicao'])): ?>
                    <div style="color: var(--neon-pink); font-size: 0.85rem; margin-top: 5px;">
                        <i class="fas fa-exclamation-circle"></i> Motivo: <?php echo sanitize($s['motivo_rejeicao']); ?>
                    </div>
                    <?php endif; ?>
                    <div style="color: var(--text-muted); font-size: 0.75rem; margin-top: 5px;">
                        Solicitado: <?php echo format_date($s['criado_em']); ?>
                        <?php if ($s['processado_em']): ?>
                            | Processado: <?php echo format_date($s['processado_em']); ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($s['status'] === 'pendente'): ?>
                <div style="display: flex; gap: 8px; flex-shrink: 0;">
                    <!-- Aprovar -->
                    <form method="POST" style="margin: 0;" onsubmit="return confirm('Aprovar este saque? O saldo ja foi debitado.')">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="acao" value="aprovar">
                        <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                        <button type="submit" class="btn btn-primary" style="padding: 8px 16px;">
                            <i class="fas fa-check"></i> Aprovar
                        </button>
                    </form>

                    <!-- Rejeitar -->
                    <button type="button" onclick="document.getElementById('rejeitar-<?php echo $s['id']; ?>').style.display='flex'"
                            class="btn btn-secondary" style="padding: 8px 16px; color: var(--neon-pink); border-color: var(--neon-pink);">
                        <i class="fas fa-times"></i> Rejeitar
                    </button>
                </div>
                <?php endif; ?>
            </div>

            <!-- Modal Rejeitar -->
            <div id="rejeitar-<?php echo $s['id']; ?>" style="display: none; margin-top: 15px; padding-top: 15px; border-top: 1px solid var(--border-color);">
                <form method="POST">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="acao" value="rejeitar">
                    <input type="hidden" name="id" value="<?php echo $s['id']; ?>">
                    <div class="form-group">
                        <label class="form-label">Motivo da Rejeicao (o usuario sera notificado)</label>
                        <input type="text" name="motivo" class="form-input" placeholder="Ex: Comprovante invalido" required>
                    </div>
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-secondary" style="color: var(--neon-pink); border-color: var(--neon-pink);">
                            <i class="fas fa-times"></i> Confirmar Rejeicao
                        </button>
                        <button type="button" onclick="document.getElementById('rejeitar-<?php echo $s['id']; ?>').style.display='none'"
                                class="btn btn-secondary">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($pagination['total_pages'] > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php echo render_pagination($pagination, "?status={$filtro}&"); ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>