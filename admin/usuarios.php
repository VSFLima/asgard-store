<?php
/**
 * Asgard Store - Admin: Gerenciar Usuários
 * Busca, filtros, ações (suspender, banir, promover admin)
 */

$page_title = 'Gerenciar Usuários';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// ============================================
// PROCESSAR AÇÕES
// ============================================

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $acao = $_POST['acao'] ?? '';
    $user_id = intval($_POST['user_id'] ?? 0);

    if ($user_id > 0 && $user_id != $_SESSION['user_id']) {
        $usuario = db_fetch("SELECT * FROM usuarios WHERE id = ?", [$user_id]);

        if ($usuario) {
            switch ($acao) {
                case 'suspender':
                    $novo_status = $usuario['status'] === 'suspenso' ? 'ativo' : 'suspenso';
                    db_update('usuarios', ['status' => $novo_status], 'id = ?', [$user_id]);
                    db_insert('admin_log', [
                        'admin_id' => $_SESSION['user_id'],
                        'acao' => ($novo_status === 'suspenso' ? 'Suspender' : 'Reativar') . ' usuário',
                        'descricao' => 'Usuário: ' . $usuario['nome'] . ' ' . $usuario['sobrenome'] . ' (' . $usuario['email'] . ')',
                        'tipo' => 'usuario'
                    ]);
                    $msg = $novo_status === 'suspenso' ? 'Usuário suspenso com sucesso!' : 'Usuário reativado com sucesso!';
                    break;

                case 'banir':
                    $novo_status = $usuario['status'] === 'banido' ? 'ativo' : 'banido';
                    db_update('usuarios', ['status' => $novo_status], 'id = ?', [$user_id]);
                    db_insert('admin_log', [
                        'admin_id' => $_SESSION['user_id'],
                        'acao' => ($novo_status === 'banido' ? 'Banir' : 'Desbanir') . ' usuário',
                        'descricao' => 'Usuário: ' . $usuario['nome'] . ' ' . $usuario['sobrenome'] . ' (' . $usuario['email'] . ')',
                        'tipo' => 'usuario'
                    ]);
                    $msg = $novo_status === 'banido' ? 'Usuário banido com sucesso!' : 'Usuário desbanido com sucesso!';
                    break;

                case 'toggle_admin':
                    $novo_admin = $usuario['admin'] ? 0 : 1;
                    db_update('usuarios', ['admin' => $novo_admin], 'id = ?', [$user_id]);
                    db_insert('admin_log', [
                        'admin_id' => $_SESSION['user_id'],
                        'acao' => ($novo_admin ? 'Promover' : 'Rebaixar') . ' admin',
                        'descricao' => 'Usuário: ' . $usuario['nome'] . ' ' . $usuario['sobrenome'] . ' (' . $usuario['email'] . ')',
                        'tipo' => 'usuario'
                    ]);
                    $msg = $novo_admin ? 'Usuário promovido a admin!' : 'Privilégios de admin removidos!';
                    break;

                case 'resetar_senha':
                    $nova_senha = bin2hex(random_bytes(6));
                    $hash = hash_password($nova_senha);
                    db_update('usuarios', ['senha' => $hash, 'senha_temporaria' => 1], 'id = ?', [$user_id]);
                    db_insert('admin_log', [
                        'admin_id' => $_SESSION['user_id'],
                        'acao' => 'Resetar senha',
                        'descricao' => 'Usuário: ' . $usuario['nome'] . ' ' . $usuario['sobrenome'] . ' | Nova senha: ' . $nova_senha,
                        'tipo' => 'usuario'
                    ]);
                    $msg = 'Senha resetada! Nova senha: <strong>' . $nova_senha . '</strong>';
                    break;
            }

            $_SESSION['flash'] = ['type' => 'success', 'message' => $msg];
        }
    } else if ($user_id == $_SESSION['user_id']) {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Você não pode alterar seu próprio status!'];
    }

    header('Location: /admin/usuarios.php' . ($_GET['q'] ? '?q=' . urlencode($_GET['q']) : ''));
    exit;
}

// ============================================
// PARÂMETROS DE BUSCA E FILTROS
// ============================================

$q = trim($_GET['q'] ?? '');
$filtro_status = $_GET['status'] ?? '';
$filtro_tipo = $_GET['tipo'] ?? '';
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 15;

// ============================================
// CONSTRUIR QUERY
// ============================================

$where = ['1=1'];
$params = [];

if ($q !== '') {
    $where[] = "(u.nome LIKE ? OR u.sobrenome LIKE ? OR u.email LIKE ? OR u.telefone LIKE ?)";
    $q_param = "%{$q}%";
    $params = array_merge($params, [$q_param, $q_param, $q_param, $q_param]);
}

if ($filtro_status !== '' && in_array($filtro_status, ['ativo', 'suspenso', 'banido'])) {
    $where[] = "u.status = ?";
    $params[] = $filtro_status;
}

if ($filtro_tipo === 'admin') {
    $where[] = "u.admin = 1";
} else if ($filtro_tipo === 'vendedor') {
    $where[] = "u.total_vendas > 0";
}

$where_sql = implode(' AND ', $where);

// Contar total
$total = db_fetch("SELECT COUNT(*) as total FROM usuarios u WHERE {$where_sql}", $params);
$total = $total['total'];

// Paginação
$pagination = paginate($total, $per_page, $page);

// Buscar usuários
$usuarios = db_fetch_all("
    SELECT u.*,
           (SELECT COUNT(*) FROM anuncios WHERE usuario_id = u.id) as total_anuncios,
           (SELECT COUNT(*) FROM compras WHERE comprador_id = u.id) as total_compras_comprador,
           (SELECT COUNT(*) FROM compras WHERE vendedor_id = u.id) as total_compras_vendedor
    FROM usuarios u
    WHERE {$where_sql}
    ORDER BY u.criado_em DESC
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
", $params);

// ============================================
// ESTATÍSTICAS RÁPIDAS
// ============================================

$stats = [
    'total' => db_count('usuarios'),
    'ativos' => db_count('usuarios', "status = 'ativo'"),
    'suspensos' => db_count('usuarios', "status = 'suspenso'"),
    'banidos' => db_count('usuarios', "status = 'banido'"),
    'admins' => db_count('usuarios', "admin = 1"),
    'novos_hoje' => db_count('usuarios', "DATE(criado_em) = CURDATE()"),
];

require_once __DIR__ . '/../includes/header.php';
?>

<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <i class="fas fa-shield-halved"></i>
            <span>Admin Panel</span>
        </div>
        <nav class="admin-nav">
            <a href="/admin/" class="admin-nav-item"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="/admin/usuarios.php" class="admin-nav-item active"><i class="fas fa-users"></i> Usuários</a>
            <a href="/admin/anuncios.php" class="admin-nav-item"><i class="fas fa-tags"></i> Anúncios</a>
            <a href="/admin/compras.php" class="admin-nav-item"><i class="fas fa-shopping-cart"></i> Compras</a>
            <a href="/admin/jogos.php" class="admin-nav-item"><i class="fas fa-gamepad"></i> Jogos</a>
            <a href="/admin/creditos.php" class="admin-nav-item"><i class="fas fa-coins"></i> Créditos</a>
            <a href="/admin/saques.php" class="admin-nav-item"><i class="fas fa-money-bill-wave"></i> Saques</a>
            <a href="/admin/redes_sociais.php" class="admin-nav-item"><i class="fas fa-share-nodes"></i> Redes Sociais</a>
            <a href="/admin/config.php" class="admin-nav-item"><i class="fas fa-gear"></i> Configurações</a>
        </nav>
    </aside>

    <main class="admin-main">
        <!-- Header -->
        <div class="admin-header">
            <div>
                <h1>Gerenciar Usuários</h1>
                <p>Visualize, busque e gerencie todos os usuários da plataforma.</p>
            </div>
        </div>

        <!-- Estatísticas Rápidas -->
        <div class="user-stats">
            <div class="user-stat-card">
                <i class="fas fa-users"></i>
                <div>
                    <strong><?php echo number_format($stats['total']); ?></strong>
                    <span>Total</span>
                </div>
            </div>
            <div class="user-stat-card active">
                <i class="fas fa-check-circle"></i>
                <div>
                    <strong><?php echo number_format($stats['ativos']); ?></strong>
                    <span>Ativos</span>
                </div>
            </div>
            <div class="user-stat-card suspended">
                <i class="fas fa-pause-circle"></i>
                <div>
                    <strong><?php echo number_format($stats['suspensos']); ?></strong>
                    <span>Suspensos</span>
                </div>
            </div>
            <div class="user-stat-card banned">
                <i class="fas fa-ban"></i>
                <div>
                    <strong><?php echo number_format($stats['banidos']); ?></strong>
                    <span>Banidos</span>
                </div>
            </div>
            <div class="user-stat-card admin">
                <i class="fas fa-shield-halved"></i>
                <div>
                    <strong><?php echo number_format($stats['admins']); ?></strong>
                    <span>Admins</span>
                </div>
            </div>
            <div class="user-stat-card new">
                <i class="fas fa-user-plus"></i>
                <div>
                    <strong><?php echo number_format($stats['novos_hoje']); ?></strong>
                    <span>Hoje</span>
                </div>
            </div>
        </div>

        <!-- Filtros e Busca -->
        <div class="admin-filters">
            <form method="GET" class="filter-form">
                <div class="filter-search">
                    <i class="fas fa-search"></i>
                    <input type="text" name="q" placeholder="Buscar por nome, email ou telefone..." value="<?php echo sanitize($q); ?>" class="form-input">
                </div>
                <div class="filter-group">
                    <select name="status" class="form-select">
                        <option value="">Todos os status</option>
                        <option value="ativo" <?php echo $filtro_status === 'ativo' ? 'selected' : ''; ?>>✅ Ativo</option>
                        <option value="suspenso" <?php echo $filtro_status === 'suspenso' ? 'selected' : ''; ?>>⏸️ Suspenso</option>
                        <option value="banido" <?php echo $filtro_status === 'banido' ? 'selected' : ''; ?>>🚫 Banido</option>
                    </select>
                    <select name="tipo" class="form-select">
                        <option value="">Todos os tipos</option>
                        <option value="admin" <?php echo $filtro_tipo === 'admin' ? 'selected' : ''; ?>>🛡️ Admin</option>
                        <option value="vendedor" <?php echo $filtro_tipo === 'vendedor' ? 'selected' : ''; ?>>💰 Vendedor</option>
                    </select>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filtrar</button>
                    <?php if ($q || $filtro_status || $filtro_tipo): ?>
                        <a href="/admin/usuarios.php" class="btn btn-outline btn-sm"><i class="fas fa-times"></i> Limpar</a>
                    <?php endif; ?>
                </div>
            </form>
            <div class="filter-results">
                <?php echo number_format($total); ?> usuário(s) encontrado(s)
                <?php if ($q): ?> para "<strong><?php echo sanitize($q); ?></strong>"<?php endif; ?>
            </div>
        </div>

        <!-- Tabela de Usuários -->
        <?php if (empty($usuarios)): ?>
        <div class="admin-panel">
            <div class="panel-empty">
                <i class="fas fa-users-slash"></i>
                <p>Nenhum usuário encontrado</p>
                <?php if ($q || $filtro_status || $filtro_tipo): ?>
                    <a href="/admin/usuarios.php" class="btn btn-outline btn-sm" style="margin-top: 10px;">Limpar filtros</a>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <div class="table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Usuário</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Anúncios</th>
                        <th>Vendas</th>
                        <th>Saldo</th>
                        <th>Cadastro</th>
                        <th>Ações</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr class="status-row-<?php echo $u['status']; ?>">
                        <td>
                            <div class="user-cell">
                                <div class="user-avatar-sm">
                                    <?php if ($u['admin']): ?>
                                        <i class="fas fa-shield-halved" style="color: var(--neon-yellow);"></i>
                                    <?php else: ?>
                                        <i class="fas fa-user-circle"></i>
                                    <?php endif; ?>
                                </div>
                                <div class="user-info">
                                    <strong><?php echo sanitize($u['nome'] . ' ' . $u['sobrenome']); ?></strong>
                                    <?php if ($u['admin']): ?>
                                        <span class="badge badge-admin">Admin</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td><?php echo sanitize($u['email']); ?></td>
                        <td>
                            <span class="status-badge status-<?php echo $u['status']; ?>">
                                <?php
                                $status_labels = [
                                    'ativo' => '✅ Ativo',
                                    'suspenso' => '⏸️ Suspenso',
                                    'banido' => '🚫 Banido'
                                ];
                                echo $status_labels[$u['status']] ?? $u['status'];
                                ?>
                            </span>
                        </td>
                        <td><?php echo $u['total_anuncios']; ?></td>
                        <td><?php echo $u['total_compras_vendedor']; ?></td>
                        <td>R$ <?php echo number_format($u['saldo'], 2, ',', '.'); ?></td>
                        <td><?php echo time_ago($u['criado_em']); ?></td>
                        <td>
                            <div class="action-buttons">
                                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                                    <!-- Suspender/Reativar -->
                                    <form method="POST" style="display: inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="acao" value="suspender">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-xs <?php echo $u['status'] === 'suspenso' ? 'btn-success' : 'btn-warning'; ?>" title="<?php echo $u['status'] === 'suspenso' ? 'Reativar' : 'Suspender'; ?>">
                                            <i class="fas <?php echo $u['status'] === 'suspenso' ? 'fa-play' : 'fa-pause'; ?>"></i>
                                        </button>
                                    </form>

                                    <!-- Banir/Desbanir -->
                                    <form method="POST" style="display: inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="acao" value="banir">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-xs <?php echo $u['status'] === 'banido' ? 'btn-success' : 'btn-danger'; ?>" title="<?php echo $u['status'] === 'banido' ? 'Desbanir' : 'Banir'; ?>" onclick="return confirm('<?php echo $u['status'] === 'banido' ? 'Desbanir este usuário?' : 'Tem certeza que deseja banir este usuário?'; ?>')">
                                            <i class="fas <?php echo $u['status'] === 'banido' ? 'fa-check' : 'fa-ban'; ?>"></i>
                                        </button>
                                    </form>

                                    <!-- Promover/Rebaixar Admin -->
                                    <form method="POST" style="display: inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="acao" value="toggle_admin">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-xs <?php echo $u['admin'] ? 'btn-outline' : 'btn-info'; ?>" title="<?php echo $u['admin'] ? 'Remover admin' : 'Promover a admin'; ?>" onclick="return confirm('<?php echo $u['admin'] ? 'Remover privilégios de admin?' : 'Promover este usuário a admin?'; ?>')">
                                            <i class="fas <?php echo $u['admin'] ? 'fa-user-minus' : 'fa-user-shield'; ?>"></i>
                                        </button>
                                    </form>

                                    <!-- Resetar Senha -->
                                    <form method="POST" style="display: inline;">
                                        <?php echo csrf_field(); ?>
                                        <input type="hidden" name="acao" value="resetar_senha">
                                        <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                        <button type="submit" class="btn btn-xs btn-outline" title="Resetar senha" onclick="return confirm('Gerar nova senha para este usuário?')">
                                            <i class="fas fa-key"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span class="text-muted" style="font-size: 0.8rem;">Você</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginação -->
        <?php echo render_pagination($pagination, '/admin/usuarios.php' . ($q ? '?q=' . urlencode($q) . '&' : '?') . ($filtro_status ? 'status=' . $filtro_status . '&' : '') . ($filtro_tipo ? 'tipo=' . $filtro_tipo : '')); ?>
        <?php endif; ?>
    </main>
</div>

<style>
/* ============================================
   USER MANAGEMENT STYLES
   ============================================ */

.user-stats {
    display: grid;
    grid-template-columns: repeat(6, 1fr);
    gap: 15px;
    margin-bottom: 25px;
}

.user-stat-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.2s;
}

.user-stat-card:hover {
    border-color: var(--neon-green);
    transform: translateY(-2px);
}

.user-stat-card i {
    font-size: 1.3rem;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    color: var(--text-secondary);
    background: rgba(255,255,255,0.05);
}

.user-stat-card.active i { color: var(--neon-green); background: rgba(0,255,136,0.1); }
.user-stat-card.suspended i { color: var(--neon-yellow); background: rgba(255,200,0,0.1); }
.user-stat-card.banned i { color: #ff4444; background: rgba(255,68,68,0.1); }
.user-stat-card.admin i { color: #8800ff; background: rgba(136,0,255,0.1); }
.user-stat-card.new i { color: var(--neon-blue); background: rgba(0,136,255,0.1); }

.user-stat-card strong {
    display: block;
    font-family: 'Orbitron', monospace;
    font-size: 1.2rem;
    color: var(--text-primary);
}

.user-stat-card span {
    font-size: 0.8rem;
    color: var(--text-secondary);
}

/* Filters */
.admin-filters {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
}

.filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    align-items: flex-end;
}

.filter-search {
    flex: 1;
    min-width: 250px;
    position: relative;
}

.filter-search i {
    position: absolute;
    left: 14px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-secondary);
}

.filter-search .form-input {
    padding-left: 40px;
    width: 100%;
}

.filter-group {
    display: flex;
    gap: 10px;
    align-items: flex-end;
}

.form-select {
    background: var(--bg-primary);
    border: 1px solid var(--border-color);
    color: var(--text-primary);
    padding: 10px 14px;
    border-radius: 8px;
    font-family: 'Rajdhani', sans-serif;
    font-size: 0.95rem;
    cursor: pointer;
}

.filter-results {
    margin-top: 12px;
    font-size: 0.85rem;
    color: var(--text-secondary);
}

/* Table */
.table-container {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
    margin-bottom: 20px;
}

.data-table {
    width: 100%;
    border-collapse: collapse;
}

.data-table th {
    background: rgba(0,255,136,0.05);
    padding: 14px 16px;
    text-align: left;
    font-size: 0.85rem;
    color: var(--neon-green);
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-bottom: 1px solid var(--border-color);
}

.data-table td {
    padding: 12px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    font-size: 0.9rem;
    color: var(--text-primary);
}

.data-table tr:hover {
    background: rgba(0,255,136,0.03);
}

.status-row-suspenso { opacity: 0.7; }
.status-row-banido { opacity: 0.5; }

/* User Cell */
.user-cell {
    display: flex;
    align-items: center;
    gap: 10px;
}

.user-avatar-sm {
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.05);
    border-radius: 8px;
    font-size: 1.2rem;
    color: var(--neon-blue);
}

.user-info strong {
    display: block;
    font-size: 0.9rem;
}

.badge {
    font-size: 0.65rem;
    padding: 2px 6px;
    border-radius: 4px;
    font-weight: 600;
    text-transform: uppercase;
}

.badge-admin {
    background: rgba(136,0,255,0.15);
    color: #8800ff;
}

/* Status Badges */
.status-badge {
    font-size: 0.75rem;
    padding: 4px 10px;
    border-radius: 6px;
    font-weight: 600;
    white-space: nowrap;
}

.status-ativo { background: rgba(0,255,136,0.15); color: var(--neon-green); }
.status-suspenso { background: rgba(255,200,0,0.15); color: var(--neon-yellow); }
.status-banido { background: rgba(255,68,68,0.15); color: #ff4444; }

/* Action Buttons */
.action-buttons {
    display: flex;
    gap: 6px;
    flex-wrap: nowrap;
}

.btn-xs {
    padding: 5px 8px;
    font-size: 0.75rem;
    border-radius: 6px;
    border: 1px solid var(--border-color);
    background: var(--bg-primary);
    color: var(--text-secondary);
    cursor: pointer;
    transition: all 0.2s;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
}

.btn-xs:hover { border-color: var(--neon-green); color: var(--neon-green); }
.btn-xs.btn-danger:hover { border-color: #ff4444; color: #ff4444; }
.btn-xs.btn-success:hover { border-color: var(--neon-green); color: var(--neon-green); }
.btn-xs.btn-warning:hover { border-color: var(--neon-yellow); color: var(--neon-yellow); }
.btn-xs.btn-info:hover { border-color: var(--neon-blue); color: var(--neon-blue); }

/* Panel Empty */
.panel-empty {
    padding: 50px 20px;
    text-align: center;
    color: var(--text-secondary);
}

.panel-empty i {
    font-size: 2.5rem;
    margin-bottom: 12px;
    display: block;
    opacity: 0.3;
}

/* Responsive */
@media (max-width: 1200px) {
    .user-stats { grid-template-columns: repeat(3, 1fr); }
}

@media (max-width: 768px) {
    .user-stats { grid-template-columns: repeat(2, 1fr); }
    .filter-form { flex-direction: column; }
    .filter-group { flex-wrap: wrap; }
    .filter-search { min-width: 100%; }
    .data-table { font-size: 0.8rem; }
    .data-table th:nth-child(5),
    .data-table td:nth-child(5),
    .data-table th:nth-child(7),
    .data-table td:nth-child(7) { display: none; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
