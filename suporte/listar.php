<?php
/**
 * Asgard Store - Listar Tickets de Suporte
 */

$page_title = 'Meus Tickets';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];

// Filtros
$filtro = $_GET['status'] ?? '';
$page_num = max(1, intval($_GET['page'] ?? 1));

$where = 't.usuario_id = ?';
$params = [$user_id];

if ($filtro && in_array($filtro, ['aberto', 'em_andamento', 'resolvido'])) {
    $where .= ' AND t.status = ?';
    $params[] = $filtro;
}

$total = db_count('suporte_tickets t', $where, $params);
$pagination = paginate($total, 15, $page_num);

$tickets = db_fetch_all(
    "SELECT t.*,
            (SELECT COUNT(*) FROM suporte_respostas WHERE ticket_id = t.id) as total_respostas,
            (SELECT r.criado_em FROM suporte_respostas r WHERE r.ticket_id = t.id ORDER BY r.criado_em DESC LIMIT 1) as ultima_resposta
     FROM suporte_tickets t
     WHERE {$where}
     ORDER BY t.criado_em DESC
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

// Stats
$total_tickets = db_count('suporte_tickets', 'usuario_id = ?', [$user_id]);
$abertos = db_count('suporte_tickets', 'usuario_id = ? AND status = ?', [$user_id, 'aberto']);
$em_andamento = db_count('suporte_tickets', 'usuario_id = ? AND status = ?', [$user_id, 'em_andamento']);
$resolvidos = db_count('suporte_tickets', 'usuario_id = ? AND status = ?', [$user_id, 'resolvido']);

$status_styles = [
    'aberto' => ['background: rgba(255,193,7,0.1); color: var(--neon-yellow);', 'Aberto'],
    'em_andamento' => ['background: rgba(0,123,255,0.1); color: var(--neon-blue);', 'Em Andamento'],
    'resolvido' => ['background: rgba(0,255,136,0.1); color: var(--neon-green);', 'Resolvido'],
];
$prioridade_colors = [
    'baixa' => 'var(--neon-green)',
    'normal' => 'var(--neon-yellow)',
    'alta' => '#ff9800',
    'urgente' => 'var(--neon-pink)',
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 30px;">
        <h1 class="section-title" style="margin: 0;">
            <i class="fas fa-headset" style="color: var(--neon-green);"></i> Meus Tickets
        </h1>
        <a href="/suporte/" class="btn btn-primary">
            <i class="fas fa-plus"></i> Novo Ticket
        </a>
    </div>

    <!-- Stats -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 10px; margin-bottom: 20px;">
        <a href="?" class="card" style="text-decoration: none; text-align: center; padding: 12px; border: <?php echo empty($filtro) ? '2px solid var(--neon-green)' : '1px solid var(--border-color)'; ?>;">
            <div style="font-size: 1.3rem; font-weight: 700; color: var(--text);"><?php echo $total_tickets; ?></div>
            <div style="color: var(--text-muted); font-size: 0.75rem;">Total</div>
        </a>
        <a href="?status=aberto" class="card" style="text-decoration: none; text-align: center; padding: 12px; border: <?php echo $filtro === 'aberto' ? '2px solid var(--neon-yellow)' : '1px solid var(--border-color)'; ?>;">
            <div style="font-size: 1.3rem; font-weight: 700; color: var(--neon-yellow);"><?php echo $abertos; ?></div>
            <div style="color: var(--text-muted); font-size: 0.75rem;">Abertos</div>
        </a>
        <a href="?status=em_andamento" class="card" style="text-decoration: none; text-align: center; padding: 12px; border: <?php echo $filtro === 'em_andamento' ? '2px solid var(--neon-blue)' : '1px solid var(--border-color)'; ?>;">
            <div style="font-size: 1.3rem; font-weight: 700; color: var(--neon-blue);"><?php echo $em_andamento; ?></div>
            <div style="color: var(--text-muted); font-size: 0.75rem;">Andamento</div>
        </a>
        <a href="?status=resolvido" class="card" style="text-decoration: none; text-align: center; padding: 12px; border: <?php echo $filtro === 'resolvido' ? '2px solid var(--neon-green)' : '1px solid var(--border-color)'; ?>;">
            <div style="font-size: 1.3rem; font-weight: 700; color: var(--neon-green);"><?php echo $resolvidos; ?></div>
            <div style="color: var(--text-muted); font-size: 0.75rem;">Resolvidos</div>
        </a>
    </div>

    <!-- Lista -->
    <?php if (empty($tickets)): ?>
        <div class="card" style="text-align: center; padding: 50px;">
            <i class="fas fa-inbox" style="font-size: 3rem; color: var(--text-muted); margin-bottom: 15px; display: block;"></i>
            <h3 style="color: var(--text); margin-bottom: 10px;">Nenhum ticket encontrado</h3>
            <p style="color: var(--text-muted); margin-bottom: 20px;">Precisa de ajuda? Abra um ticket de suporte.</p>
            <a href="/suporte/" class="btn btn-primary"><i class="fas fa-plus"></i> Abrir Ticket</a>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <?php foreach ($tickets as $t): ?>
            <?php
            $ss = $status_styles[$t['status']] ?? ['background: var(--bg-input); color: var(--text-muted);', ucfirst($t['status'])];
            $pc = $prioridade_colors[$t['prioridade']] ?? 'var(--text-muted)';
            $ultima = $t['ultima_resposta'] ? time_ago($t['ultima_resposta']) : 'Sem resposta';
            ?>
            <a href="/suporte/ticket.php?id=<?php echo $t['id']; ?>" 
               class="card" 
               style="text-decoration: none; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; transition: all 0.2s;">
                <div style="flex: 1; min-width: 200px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 5px;">
                        <span style="color: var(--text-muted); font-size: 0.85rem; font-weight: 600;">#<?php echo $t['id']; ?></span>
                        <span style="color: var(--pc); font-size: 0.7rem;">
                            <i class="fas fa-flag" style="color: <?php echo $pc; ?>;"></i>
                        </span>
                        <strong style="color: var(--text);"><?php echo sanitize($t['assunto']); ?></strong>
                    </div>
                    <div style="color: var(--text-muted); font-size: 0.8rem;">
                        Criado: <?php echo format_date($t['criado_em']); ?>
                        <?php echo $t['total_respostas'] > 0 ? " | {$t['total_respostas']} resposta(s) | Ultima: {$ultima}" : ' | Sem resposta'; ?>
                    </div>
                </div>
                <span style="<?php echo $ss[0]; ?> padding: 4px 12px; border-radius: 12px; font-size: 0.8rem; font-weight: 600; white-space: nowrap;">
                    <?php echo $ss[1]; ?>
                </span>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if ($pagination['total_pages'] > 1): ?>
        <div style="margin-top: 20px; text-align: center;">
            <?php echo render_pagination($pagination, '?status=' . urlencode($filtro) . '&'); ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>