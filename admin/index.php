<?php
/**
 * Asgard Store - Admin Dashboard
 * Métricas, gráficos e atividade recente
 */

$page_title = 'Painel Administrativo';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// ============================================
// MÉTRICAS PRINCIPAIS
// ============================================

// Usuários
$total_usuarios = db_count('usuarios');
$usuarios_ativos = db_count('usuarios', "status = 'ativo'");
$usuarios_novos_hoje = db_count('usuarios', "DATE(criado_em) = CURDATE()");
$usuarios_novos_mes = db_count('usuarios', "MONTH(criado_em) = MONTH(CURDATE()) AND YEAR(criado_em) = YEAR(CURDATE())");

// Anúncios
$total_anuncios = db_count('anuncios');
$anuncios_aprovados = db_count('anuncios', "status = 'aprovado'");
$anuncios_pendentes = db_count('anuncios', "status = 'pendente'");
$anuncios_reprovados = db_count('anuncios', "status = 'reprovado'");
$anuncios_vendidos = db_count('anuncios', "status = 'vendido'");

// Compras
$total_compras = db_count('compras');
$compras_hoje = db_count('compras', "DATE(criado_em) = CURDATE()");
$compras_pendentes = db_count('compras', "status = 'aguardando_pagamento'");
$compras_em_andamento = db_count('compras', "status IN ('pagamento_confirmado', 'entregando')");

// Compras de Créditos
$total_compra_creditos = db_count('compra_creditos');
$creditos_pendentes = db_count('compra_creditos', "status = 'pendente'");

// Financeiro
$comissao_padrao = db_fetch("SELECT valor FROM configuracoes WHERE chave = 'comissao_padrao'");
$comissao_percent = $comissao_padrao ? floatval($comissao_padrao['valor']) : 10;

$faturamento_total = db_fetch("SELECT COALESCE(SUM(valor), 0) as total FROM compras WHERE status IN ('concluido', 'entregue')");
$faturamento_total = floatval($faturamento_total['total'] ?? 0);

$faturamento_mes = db_fetch("SELECT COALESCE(SUM(valor), 0) as total FROM compras WHERE status IN ('concluido', 'entregue') AND MONTH(criado_em) = MONTH(CURDATE()) AND YEAR(criado_em) = YEAR(CURDATE())");
$faturamento_mes = floatval($faturamento_mes['total'] ?? 0);

$faturamento_hoje = db_fetch("SELECT COALESCE(SUM(valor), 0) as total FROM compras WHERE status IN ('concluido', 'entregue') AND DATE(criado_em) = CURDATE()");
$faturamento_hoje = floatval($faturamento_hoje['total'] ?? 0);

$comissao_total = $faturamento_total * ($comissao_percent / 100);
$comissao_mes = $faturamento_mes * ($comissao_percent / 100);

// Saques
$saques_pendentes = db_count('saques', "status = 'pendente'");
$saques_processando = db_count('saques', "status = 'processando'");
$saques_valor_pendente = db_fetch("SELECT COALESCE(SUM(valor), 0) as total FROM saques WHERE status IN ('pendente', 'processando')");
$saques_valor_pendente = floatval($saques_valor_pendente['total'] ?? 0);

// Suporte
$tickets_abertos = db_count('suporte_tickets', "status = 'aberto'");
$tickets_andamento = db_count('suporte_tickets', "status = 'em_andamento'");

// Disputas
$disputas_abertas = db_count('disputas', "status IN ('aberta', 'em_analise')");

// Jogos
$total_jogos = db_count('jogos', "ativo = 1");
$total_creditos_pacotes = db_count('creditos', "ativo = 1");

// ============================================
// DADOS PARA GRÁFICOS (últimos 7 dias)
// ============================================

$chart_labels = [];
$chart_vendas = [];
$chart_faturamento = [];
$chart_usuarios = [];

for ($i = 6; $i >= 0; $i--) {
    $data = date('Y-m-d', strtotime("-{$i} days"));
    $data_display = date('d/m', strtotime("-{$i} days"));
    $chart_labels[] = $data_display;

    // Vendas do dia
    $vendas_dia = db_count('compras', "DATE(criado_em) = ? AND status IN ('concluido', 'entregue')", [$data]);
    $chart_vendas[] = $vendas_dia;

    // Faturamento do dia
    $fat_dia = db_fetch("SELECT COALESCE(SUM(valor), 0) as total FROM compras WHERE DATE(criado_em) = ? AND status IN ('concluido', 'entregue')", [$data]);
    $chart_faturamento[] = floatval($fat_dia['total'] ?? 0);

    // Novos usuários do dia
    $users_dia = db_count('usuarios', "DATE(criado_em) = ?", [$data]);
    $chart_usuarios[] = $users_dia;
}

// ============================================
// ATIVIDADE RECENTE
// ============================================

$atividade_recente = db_fetch_all("
    SELECT al.*, u.nome, u.sobrenome
    FROM admin_log al
    LEFT JOIN usuarios u ON al.admin_id = u.id
    ORDER BY al.criado_em DESC
    LIMIT 10
");

// ============================================
// ÚLTIMAS COMPRAS
// ============================================

$ultimas_compras = db_fetch_all("
    SELECT c.*, 
           a.titulo as anuncio_titulo,
           j.nome as jogo_nome,
           uc.nome as comprador_nome, uc.sobrenome as comprador_sobrenome,
           uv.nome as vendedor_nome, uv.sobrenome as vendedor_sobrenome
    FROM compras c
    LEFT JOIN anuncios a ON c.anuncio_id = a.id
    LEFT JOIN jogos j ON a.jogo_id = j.id
    LEFT JOIN usuarios uc ON c.comprador_id = uc.id
    LEFT JOIN usuarios uv ON c.vendedor_id = uv.id
    ORDER BY c.criado_em DESC
    LIMIT 8
");

// ============================================
// ANÚNCIOS PENDENTES
// ============================================

$anuncios_pendentes_lista = db_fetch_all("
    SELECT a.*, u.nome, u.sobrenome, j.nome as jogo_nome
    FROM anuncios a
    LEFT JOIN usuarios u ON a.usuario_id = u.id
    LEFT JOIN jogos j ON a.jogo_id = j.id
    WHERE a.status = 'pendente'
    ORDER BY a.criado_em ASC
    LIMIT 5
");

// ============================================
// TOP VENDEDORES
// ============================================

$top_vendedores = db_fetch_all("
    SELECT u.id, u.nome, u.sobrenome, u.avatar,
           COUNT(c.id) as total_vendas,
           SUM(c.valor) as valor_total,
           u.nota_media
    FROM usuarios u
    INNER JOIN compras c ON u.id = c.vendedor_id
    WHERE c.status IN ('concluido', 'entregue')
    GROUP BY u.id
    ORDER BY valor_total DESC
    LIMIT 5
");

// ============================================
// JOGOS MAIS VENDIDOS
// ============================================

$jogos_mais_vendidos = db_fetch_all("
    SELECT j.nome, j.slug, j.icone,
           COUNT(c.id) as total_vendas,
           SUM(c.valor) as valor_total
    FROM jogos j
    INNER JOIN anuncios a ON j.id = a.jogo_id
    INNER JOIN compras c ON a.id = c.anuncio_id
    WHERE c.status IN ('concluido', 'entregue')
    GROUP BY j.id
    ORDER BY total_vendas DESC
    LIMIT 5
");

require_once __DIR__ . '/../includes/header.php';
?>

<!-- Admin Sidebar + Content -->
<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <div class="admin-sidebar-header">
            <i class="fas fa-shield-halved"></i>
            <span>Admin Panel</span>
        </div>
        <nav class="admin-nav">
            <a href="/admin/" class="admin-nav-item active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="/admin/usuarios.php" class="admin-nav-item"><i class="fas fa-users"></i> Usuários</a>
            <a href="/admin/anuncios.php" class="admin-nav-item"><i class="fas fa-tags"></i> Anúncios <?php if ($anuncios_pendentes > 0): ?><span class="admin-badge"><?php echo $anuncios_pendentes; ?></span><?php endif; ?></a>
            <a href="/admin/compras.php" class="admin-nav-item"><i class="fas fa-shopping-cart"></i> Compras</a>
            <a href="/admin/jogos.php" class="admin-nav-item"><i class="fas fa-gamepad"></i> Jogos</a>
            <a href="/admin/creditos.php" class="admin-nav-item"><i class="fas fa-coins"></i> Créditos</a>
            <a href="/admin/saques.php" class="admin-nav-item"><i class="fas fa-money-bill-wave"></i> Saques <?php if ($saques_pendentes > 0): ?><span class="admin-badge warning"><?php echo $saques_pendentes; ?></span><?php endif; ?></a>
            <a href="/admin/redes_sociais.php" class="admin-nav-item"><i class="fas fa-share-nodes"></i> Redes Sociais</a>
            <a href="/admin/config.php" class="admin-nav-item"><i class="fas fa-gear"></i> Configurações</a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="admin-main">
        <!-- Header -->
        <div class="admin-header">
            <div>
                <h1>Dashboard</h1>
                <p>Bem-vindo, <?php echo sanitize($_SESSION['user_nome']); ?>! Aqui está o resumo da loja.</p>
            </div>
            <div class="admin-header-actions">
                <span class="admin-date"><i class="fas fa-calendar"></i> <?php echo date('d/m/Y'); ?></span>
            </div>
        </div>

        <!-- Alertas Pendentes -->
        <?php if ($anuncios_pendentes > 0 || $saques_pendentes > 0 || $tickets_abertos > 0 || $disputas_abertas > 0): ?>
        <div class="admin-alerts">
            <?php if ($anuncios_pendentes > 0): ?>
            <a href="/admin/anuncios.php" class="admin-alert alert-warning">
                <i class="fas fa-clock"></i>
                <span><?php echo $anuncios_pendentes; ?> anúncio(s) aguardando aprovação</span>
                <i class="fas fa-arrow-right"></i>
            </a>
            <?php endif; ?>
            <?php if ($saques_pendentes > 0): ?>
            <a href="/admin/saques.php" class="admin-alert alert-info">
                <i class="fas fa-money-bill"></i>
                <span><?php echo $saques_pendentes; ?> saque(s) pendente(s) — R$ <?php echo number_format($saques_valor_pendente, 2, ',', '.'); ?></span>
                <i class="fas fa-arrow-right"></i>
            </a>
            <?php endif; ?>
            <?php if ($tickets_abertos > 0): ?>
            <a href="/suporte/" class="admin-alert alert-danger">
                <i class="fas fa-headset"></i>
                <span><?php echo $tickets_abertos; ?> ticket(s) de suporte aberto(s)</span>
                <i class="fas fa-arrow-right"></i>
            </a>
            <?php endif; ?>
            <?php if ($disputas_abertas > 0): ?>
            <a href="/admin/compras.php" class="admin-alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i>
                <span><?php echo $disputas_abertas; ?> disputa(s) aberta(s)</span>
                <i class="fas fa-arrow-right"></i>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- Métricas Principais -->
        <div class="admin-metrics">
            <div class="metric-card">
                <div class="metric-icon blue"><i class="fas fa-users"></i></div>
                <div class="metric-info">
                    <span class="metric-value"><?php echo number_format($total_usuarios); ?></span>
                    <span class="metric-label">Usuários</span>
                    <span class="metric-change positive">+<?php echo $usuarios_novos_mes; ?> este mês</span>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon green"><i class="fas fa-tags"></i></div>
                <div class="metric-info">
                    <span class="metric-value"><?php echo number_format($total_anuncios); ?></span>
                    <span class="metric-label">Anúncios</span>
                    <span class="metric-change"><?php echo $anuncios_pendentes; ?> pendentes</span>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon purple"><i class="fas fa-shopping-cart"></i></div>
                <div class="metric-info">
                    <span class="metric-value"><?php echo number_format($total_compras); ?></span>
                    <span class="metric-label">Vendas</span>
                    <span class="metric-change positive">+<?php echo $compras_hoje; ?> hoje</span>
                </div>
            </div>
            <div class="metric-card">
                <div class="metric-icon gold"><i class="fas fa-dollar-sign"></i></div>
                <div class="metric-info">
                    <span class="metric-value">R$ <?php echo number_format($faturamento_mes, 2, ',', '.'); ?></span>
                    <span class="metric-label">Faturamento (Mês)</span>
                    <span class="metric-change">R$ <?php echo number_format($comissao_mes, 2, ',', '.'); ?> comissão</span>
                </div>
            </div>
        </div>

        <!-- Métricas Secundárias -->
        <div class="admin-metrics secondary">
            <div class="metric-card-sm">
                <i class="fas fa-wallet"></i>
                <div>
                    <strong>R$ <?php echo number_format($faturamento_total, 2, ',', '.'); ?></strong>
                    <span>Faturamento Total</span>
                </div>
            </div>
            <div class="metric-card-sm">
                <i class="fas fa-percent"></i>
                <div>
                    <strong><?php echo $comissao_percent; ?>%</strong>
                    <span>Comissão Padrão</span>
                </div>
            </div>
            <div class="metric-card-sm">
                <i class="fas fa-ban"></i>
                <div>
                    <strong><?php echo $anuncios_reprovados; ?></strong>
                    <span>Anúncios Reprovados</span>
                </div>
            </div>
            <div class="metric-card-sm">
                <i class="fas fa-gamepad"></i>
                <div>
                    <strong><?php echo $total_jogos; ?></strong>
                    <span>Jogos Ativos</span>
                </div>
            </div>
            <div class="metric-card-sm">
                <i class="fas fa-coins"></i>
                <div>
                    <strong><?php echo $total_creditos_pacotes; ?></strong>
                    <span>Pacotes de Créditos</span>
                </div>
            </div>
            <div class="metric-card-sm warning">
                <i class="fas fa-headset"></i>
                <div>
                    <strong><?php echo ($tickets_abertos + $tickets_andamento); ?></strong>
                    <span>Suporte Aberto</span>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div class="admin-charts">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-line"></i> Vendas dos Últimos 7 Dias</h3>
                </div>
                <canvas id="chartVendas" height="280"></canvas>
            </div>
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-bar"></i> Faturamento dos Últimos 7 Dias</h3>
                </div>
                <canvas id="chartFaturamento" height="280"></canvas>
            </div>
        </div>

        <div class="admin-charts">
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-chart-pie"></i> Status dos Anúncios</h3>
                </div>
                <canvas id="chartAnuncios" height="280"></canvas>
            </div>
            <div class="chart-card">
                <div class="chart-header">
                    <h3><i class="fas fa-user-plus"></i> Novos Usuários (7 Dias)</h3>
                </div>
                <canvas id="chartUsuarios" height="280"></canvas>
            </div>
        </div>

        <!-- Duas Colunas: Anúncios Pendentes + Top Vendedores -->
        <div class="admin-grid-2">
            <!-- Anúncios Pendentes -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h3><i class="fas fa-clock"></i> Anúncios Pendentes</h3>
                    <a href="/admin/anuncios.php" class="panel-link">Ver todos</a>
                </div>
                <?php if (empty($anuncios_pendentes_lista)): ?>
                <div class="panel-empty">
                    <i class="fas fa-check-circle"></i>
                    <p>Nenhum anúncio pendente</p>
                </div>
                <?php else: ?>
                <div class="panel-list">
                    <?php foreach ($anuncios_pendentes_lista as $anuncio): ?>
                    <div class="panel-item">
                        <div class="panel-item-info">
                            <strong><?php echo sanitize($anuncio['titulo']); ?></strong>
                            <span><?php echo sanitize($anuncio['nome'] . ' ' . $anuncio['sobrenome']); ?> • <?php echo sanitize($anuncio['jogo_nome']); ?></span>
                            <span class="text-muted"><?php echo time_ago($anuncio['criado_em']); ?></span>
                        </div>
                        <div class="panel-item-value">
                            R$ <?php echo number_format($anuncio['preco'], 2, ',', '.'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Top Vendedores -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h3><i class="fas fa-trophy"></i> Top Vendedores</h3>
                    <a href="/admin/usuarios.php" class="panel-link">Ver todos</a>
                </div>
                <?php if (empty($top_vendedores)): ?>
                <div class="panel-empty">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhuma venda registrada</p>
                </div>
                <?php else: ?>
                <div class="panel-list">
                    <?php foreach ($top_vendedores as $idx => $vendedor): ?>
                    <div class="panel-item">
                        <div class="panel-item-rank"><?php echo $idx + 1; ?>°</div>
                        <div class="panel-item-info">
                            <strong><?php echo sanitize($vendedor['nome'] . ' ' . $vendedor['sobrenome']); ?></strong>
                            <span><?php echo $vendedor['total_vendas']; ?> venda(s) • Nota: <?php echo number_format($vendedor['nota_media'], 1); ?></span>
                        </div>
                        <div class="panel-item-value green">
                            R$ <?php echo number_format($vendedor['valor_total'], 2, ',', '.'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Duas Colunas: Últimas Vendas + Jogos Mais Vendidos -->
        <div class="admin-grid-2">
            <!-- Últimas Vendas -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h3><i class="fas fa-shopping-bag"></i> Últimas Vendas</h3>
                    <a href="/admin/compras.php" class="panel-link">Ver todas</a>
                </div>
                <?php if (empty($ultimas_compras)): ?>
                <div class="panel-empty">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhuma venda ainda</p>
                </div>
                <?php else: ?>
                <div class="panel-list">
                    <?php foreach ($ultimas_compras as $compra): ?>
                    <div class="panel-item">
                        <div class="panel-item-info">
                            <strong><?php echo sanitize($compra['anuncio_titulo'] ?? 'Anúncio #' . $compra['anuncio_id']); ?></strong>
                            <span><?php echo sanitize($compra['comprador_nome'] ?? ''); ?> → <?php echo sanitize($compra['vendedor_nome'] ?? ''); ?></span>
                            <span class="text-muted"><?php echo time_ago($compra['criado_em']); ?></span>
                        </div>
                        <div class="panel-item-right">
                            <span class="panel-item-value">R$ <?php echo number_format($compra['valor'], 2, ',', '.'); ?></span>
                            <span class="status-badge status-<?php echo $compra['status']; ?>"><?php echo ucfirst(str_replace('_', ' ', $compra['status'])); ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Jogos Mais Vendidos -->
            <div class="admin-panel">
                <div class="panel-header">
                    <h3><i class="fas fa-fire"></i> Jogos Mais Vendidos</h3>
                    <a href="/admin/jogos.php" class="panel-link">Ver todos</a>
                </div>
                <?php if (empty($jogos_mais_vendidos)): ?>
                <div class="panel-empty">
                    <i class="fas fa-inbox"></i>
                    <p>Nenhuma venda registrada</p>
                </div>
                <?php else: ?>
                <div class="panel-list">
                    <?php foreach ($jogos_mais_vendidos as $idx => $jogo): ?>
                    <div class="panel-item">
                        <div class="panel-item-rank"><?php echo $idx + 1; ?>°</div>
                        <div class="panel-item-info">
                            <strong><?php echo sanitize($jogo['nome']); ?></strong>
                            <span><?php echo $jogo['total_vendas']; ?> venda(s)</span>
                        </div>
                        <div class="panel-item-value green">
                            R$ <?php echo number_format($jogo['valor_total'], 2, ',', '.'); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Atividade Recente -->
        <div class="admin-panel full-width">
            <div class="panel-header">
                <h3><i class="fas fa-history"></i> Atividade Recente</h3>
            </div>
            <?php if (empty($atividade_recente)): ?>
            <div class="panel-empty">
                <i class="fas fa-inbox"></i>
                <p>Nenhuma atividade registrada</p>
            </div>
            <?php else: ?>
            <div class="activity-list">
                <?php foreach ($atividade_recente as $atividade): ?>
                <div class="activity-item">
                    <div class="activity-icon type-<?php echo $atividade['tipo']; ?>">
                        <?php
                        $icon_map = [
                            'aprovacao' => 'fa-check',
                            'reprovacao' => 'fa-times',
                            'usuario' => 'fa-user',
                            'config' => 'fa-cog',
                            'financeiro' => 'fa-dollar-sign',
                        ];
                        $icon = $icon_map[$atividade['tipo']] ?? 'fa-circle';
                        ?>
                        <i class="fas <?php echo $icon; ?>"></i>
                    </div>
                    <div class="activity-info">
                        <strong><?php echo sanitize(($atividade['nome'] ?? 'Admin') . ' ' . ($atividade['sobrenome'] ?? '')); ?></strong>
                        <span><?php echo sanitize($atividade['acao']); ?></span>
                        <?php if ($atividade['descricao']): ?>
                        <p class="text-muted"><?php echo sanitize($atividade['descricao']); ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="activity-time">
                        <?php echo time_ago($atividade['criado_em']); ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// ============================================
// CONFIGURAÇÃO DOS GRÁFICOS
// ============================================

const chartDefaults = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            labels: { color: '#a0aec0', font: { family: 'Rajdhani' } }
        }
    },
    scales: {
        x: {
            ticks: { color: '#718096', font: { family: 'Rajdhani' } },
            grid: { color: 'rgba(255,255,255,0.05)' }
        },
        y: {
            ticks: { color: '#718096', font: { family: 'Rajdhani' } },
            grid: { color: 'rgba(255,255,255,0.05)' }
        }
    }
};

// Gráfico de Vendas
new Chart(document.getElementById('chartVendas'), {
    type: 'line',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Vendas',
            data: <?php echo json_encode($chart_vendas); ?>,
            borderColor: '#00ff88',
            backgroundColor: 'rgba(0, 255, 136, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: '#00ff88',
            pointBorderColor: '#0a0a0f',
            pointBorderWidth: 2,
            pointRadius: 5,
            pointHoverRadius: 8
        }]
    },
    options: {
        ...chartDefaults,
        plugins: {
            ...chartDefaults.plugins,
            tooltip: {
                backgroundColor: '#1a1a2e',
                titleColor: '#00ff88',
                bodyColor: '#a0aec0',
                borderColor: '#00ff88',
                borderWidth: 1,
                callbacks: {
                    label: ctx => ctx.parsed.y + ' venda(s)'
                }
            }
        }
    }
});

// Gráfico de Faturamento
new Chart(document.getElementById('chartFaturamento'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Faturamento (R$)',
            data: <?php echo json_encode($chart_faturamento); ?>,
            backgroundColor: [
                'rgba(0, 136, 255, 0.7)',
                'rgba(136, 0, 255, 0.7)',
                'rgba(255, 0, 136, 0.7)',
                'rgba(0, 255, 136, 0.7)',
                'rgba(255, 200, 0, 0.7)',
                'rgba(255, 100, 0, 0.7)',
                'rgba(0, 200, 200, 0.7)'
            ],
            borderColor: [
                '#0088ff',
                '#8800ff',
                '#ff0088',
                '#00ff88',
                '#ffc800',
                '#ff6400',
                '#00c8c8'
            ],
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        ...chartDefaults,
        plugins: {
            ...chartDefaults.plugins,
            tooltip: {
                backgroundColor: '#1a1a2e',
                titleColor: '#0088ff',
                bodyColor: '#a0aec0',
                borderColor: '#0088ff',
                borderWidth: 1,
                callbacks: {
                    label: ctx => 'R$ ' + ctx.parsed.y.toFixed(2).replace('.', ',')
                }
            }
        }
    }
});

// Gráfico de Status dos Anúncios (Donut)
new Chart(document.getElementById('chartAnuncios'), {
    type: 'doughnut',
    data: {
        labels: ['Aprovados', 'Pendentes', 'Reprovados', 'Vendidos'],
        datasets: [{
            data: [<?php echo $anuncios_aprovados; ?>, <?php echo $anuncios_pendentes; ?>, <?php echo $anuncios_reprovados; ?>, <?php echo $anuncios_vendidos; ?>],
            backgroundColor: ['#00ff88', '#ffc800', '#ff4444', '#0088ff'],
            borderColor: '#0a0a0f',
            borderWidth: 3,
            hoverOffset: 8
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '65%',
        plugins: {
            legend: {
                position: 'bottom',
                labels: { color: '#a0aec0', font: { family: 'Rajdhani', size: 13 }, padding: 16, usePointStyle: true }
            },
            tooltip: {
                backgroundColor: '#1a1a2e',
                bodyColor: '#a0aec0',
                borderColor: '#333',
                borderWidth: 1
            }
        }
    }
});

// Gráfico de Novos Usuários
new Chart(document.getElementById('chartUsuarios'), {
    type: 'bar',
    data: {
        labels: <?php echo json_encode($chart_labels); ?>,
        datasets: [{
            label: 'Novos Usuários',
            data: <?php echo json_encode($chart_usuarios); ?>,
            backgroundColor: 'rgba(136, 0, 255, 0.6)',
            borderColor: '#8800ff',
            borderWidth: 2,
            borderRadius: 8,
            borderSkipped: false
        }]
    },
    options: {
        ...chartDefaults,
        plugins: {
            ...chartDefaults.plugins,
            tooltip: {
                backgroundColor: '#1a1a2e',
                titleColor: '#8800ff',
                bodyColor: '#a0aec0',
                borderColor: '#8800ff',
                borderWidth: 1,
                callbacks: {
                    label: ctx => ctx.parsed.y + ' usuário(s)'
                }
            }
        }
    }
});
</script>

<style>
/* ============================================
   ADMIN DASHBOARD STYLES
   ============================================ */

.admin-layout {
    display: flex;
    min-height: calc(100vh - 120px);
}

/* Sidebar */
.admin-sidebar {
    width: 260px;
    background: var(--bg-card);
    border-right: 1px solid var(--border-color);
    padding: 0;
    flex-shrink: 0;
    position: sticky;
    top: 70px;
    height: calc(100vh - 70px);
    overflow-y: auto;
}

.admin-sidebar-header {
    padding: 24px 20px;
    border-bottom: 1px solid var(--border-color);
    display: flex;
    align-items: center;
    gap: 12px;
    font-family: 'Orbitron', monospace;
    font-size: 1rem;
    color: var(--neon-green);
}

.admin-sidebar-header i {
    font-size: 1.3rem;
}

.admin-nav {
    padding: 12px 0;
}

.admin-nav-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 20px;
    color: var(--text-secondary);
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.2s;
    border-left: 3px solid transparent;
}

.admin-nav-item:hover {
    background: rgba(0, 255, 136, 0.05);
    color: var(--text-primary);
    border-left-color: var(--neon-green);
}

.admin-nav-item.active {
    background: rgba(0, 255, 136, 0.1);
    color: var(--neon-green);
    border-left-color: var(--neon-green);
    font-weight: 600;
}

.admin-nav-item i {
    width: 20px;
    text-align: center;
    font-size: 1rem;
}

.admin-badge {
    background: var(--neon-green);
    color: #000;
    font-size: 0.7rem;
    font-weight: 700;
    padding: 2px 7px;
    border-radius: 10px;
    margin-left: auto;
}

.admin-badge.warning {
    background: var(--neon-yellow);
}

/* Main Content */
.admin-main {
    flex: 1;
    padding: 30px;
    min-width: 0;
}

.admin-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 30px;
}

.admin-header h1 {
    font-family: 'Orbitron', monospace;
    font-size: 1.8rem;
    margin: 0 0 5px 0;
    background: linear-gradient(135deg, var(--neon-green), var(--neon-blue));
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.admin-header p {
    color: var(--text-secondary);
    margin: 0;
}

.admin-date {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    padding: 8px 16px;
    border-radius: 8px;
    color: var(--text-secondary);
    font-size: 0.9rem;
}

.admin-date i { margin-right: 6px; color: var(--neon-green); }

/* Alertas */
.admin-alerts {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 25px;
}

.admin-alert {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 0.95rem;
    transition: all 0.2s;
    border: 1px solid;
}

.admin-alert:hover { transform: translateX(4px); }
.admin-alert i:first-child { font-size: 1.1rem; }
.admin-alert span { flex: 1; }
.admin-alert i:last-child { opacity: 0.5; }

.alert-warning { background: rgba(255, 200, 0, 0.08); border-color: rgba(255, 200, 0, 0.3); color: var(--neon-yellow); }
.alert-info { background: rgba(0, 136, 255, 0.08); border-color: rgba(0, 136, 255, 0.3); color: var(--neon-blue); }
.alert-danger { background: rgba(255, 68, 68, 0.08); border-color: rgba(255, 68, 68, 0.3); color: #ff4444; }

/* Métricas Principais */
.admin-metrics {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.admin-metrics.secondary {
    grid-template-columns: repeat(6, 1fr);
    gap: 15px;
    margin-bottom: 30px;
}

.metric-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 16px;
    transition: all 0.3s;
}

.metric-card:hover {
    border-color: var(--neon-green);
    transform: translateY(-2px);
    box-shadow: 0 8px 24px rgba(0, 255, 136, 0.1);
}

.metric-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
}

.metric-icon.blue { background: rgba(0, 136, 255, 0.15); color: var(--neon-blue); }
.metric-icon.green { background: rgba(0, 255, 136, 0.15); color: var(--neon-green); }
.metric-icon.purple { background: rgba(136, 0, 255, 0.15); color: #8800ff; }
.metric-icon.gold { background: rgba(255, 200, 0, 0.15); color: var(--neon-yellow); }

.metric-info {
    display: flex;
    flex-direction: column;
}

.metric-value {
    font-family: 'Orbitron', monospace;
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--text-primary);
}

.metric-label {
    color: var(--text-secondary);
    font-size: 0.85rem;
    margin-top: 2px;
}

.metric-change {
    font-size: 0.8rem;
    margin-top: 4px;
    color: var(--text-secondary);
}

.metric-change.positive { color: var(--neon-green); }

/* Métricas Secundárias */
.metric-card-sm {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 10px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: all 0.2s;
}

.metric-card-sm:hover {
    border-color: var(--neon-green);
}

.metric-card-sm i {
    font-size: 1.2rem;
    color: var(--neon-green);
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(0, 255, 136, 0.1);
    border-radius: 8px;
}

.metric-card-sm.warning i {
    color: var(--neon-yellow);
    background: rgba(255, 200, 0, 0.1);
}

.metric-card-sm strong {
    display: block;
    font-family: 'Orbitron', monospace;
    font-size: 1rem;
    color: var(--text-primary);
}

.metric-card-sm span {
    font-size: 0.78rem;
    color: var(--text-secondary);
}

/* Gráficos */
.admin-charts {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

.chart-card {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    padding: 20px;
}

.chart-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.chart-header h3 {
    font-size: 1rem;
    color: var(--text-primary);
    margin: 0;
}

.chart-header h3 i {
    color: var(--neon-green);
    margin-right: 8px;
}

/* Grid 2 colunas */
.admin-grid-2 {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
    margin-bottom: 25px;
}

/* Panels */
.admin-panel {
    background: var(--bg-card);
    border: 1px solid var(--border-color);
    border-radius: 12px;
    overflow: hidden;
}

.admin-panel.full-width {
    margin-bottom: 30px;
}

.panel-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 16px 20px;
    border-bottom: 1px solid var(--border-color);
}

.panel-header h3 {
    font-size: 1rem;
    margin: 0;
    color: var(--text-primary);
}

.panel-header h3 i {
    color: var(--neon-green);
    margin-right: 8px;
}

.panel-link {
    color: var(--neon-green);
    text-decoration: none;
    font-size: 0.85rem;
    transition: opacity 0.2s;
}

.panel-link:hover { opacity: 0.7; }

.panel-empty {
    padding: 40px 20px;
    text-align: center;
    color: var(--text-secondary);
}

.panel-empty i {
    font-size: 2rem;
    margin-bottom: 10px;
    display: block;
    color: var(--neon-green);
    opacity: 0.5;
}

.panel-list {
    max-height: 350px;
    overflow-y: auto;
}

.panel-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 14px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
    transition: background 0.2s;
}

.panel-item:hover { background: rgba(0, 255, 136, 0.03); }
.panel-item:last-child { border-bottom: none; }

.panel-item-rank {
    font-family: 'Orbitron', monospace;
    font-size: 1rem;
    font-weight: 700;
    color: var(--neon-yellow);
    width: 36px;
    text-align: center;
}

.panel-item-info {
    flex: 1;
    min-width: 0;
}

.panel-item-info strong {
    display: block;
    color: var(--text-primary);
    font-size: 0.95rem;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.panel-item-info span {
    display: block;
    font-size: 0.8rem;
    color: var(--text-secondary);
    margin-top: 2px;
}

.panel-item-value {
    font-family: 'Orbitron', monospace;
    font-weight: 600;
    color: var(--text-primary);
    font-size: 0.9rem;
    white-space: nowrap;
}

.panel-item-value.green { color: var(--neon-green); }

.panel-item-right {
    text-align: right;
    display: flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 4px;
}

/* Status Badges */
.status-badge {
    font-size: 0.7rem;
    padding: 3px 8px;
    border-radius: 6px;
    font-weight: 600;
    text-transform: uppercase;
    white-space: nowrap;
}

.status-aguardando_pagamento, .status-pendente { background: rgba(255, 200, 0, 0.15); color: var(--neon-yellow); }
.status-pagamento_confirmado, .status-em_andamento { background: rgba(0, 136, 255, 0.15); color: var(--neon-blue); }
.status-entregando, .status-entregue { background: rgba(0, 255, 136, 0.15); color: var(--neon-green); }
.status-concluido { background: rgba(0, 255, 136, 0.2); color: var(--neon-green); }
.status-cancelado, .status-reprovado { background: rgba(255, 68, 68, 0.15); color: #ff4444; }
.status-em_disputa { background: rgba(255, 100, 0, 0.15); color: #ff6400; }

/* Atividade Recente */
.activity-list {
    max-height: 400px;
    overflow-y: auto;
}

.activity-item {
    display: flex;
    align-items: flex-start;
    gap: 14px;
    padding: 14px 20px;
    border-bottom: 1px solid rgba(255,255,255,0.04);
}

.activity-item:last-child { border-bottom: none; }

.activity-icon {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    flex-shrink: 0;
    margin-top: 2px;
}

.type-aprovacao { background: rgba(0, 255, 136, 0.15); color: var(--neon-green); }
.type-reprovacao { background: rgba(255, 68, 68, 0.15); color: #ff4444; }
.type-usuario { background: rgba(0, 136, 255, 0.15); color: var(--neon-blue); }
.type-config { background: rgba(255, 200, 0, 0.15); color: var(--neon-yellow); }
.type-financeiro { background: rgba(136, 0, 255, 0.15); color: #8800ff; }

.activity-info {
    flex: 1;
    min-width: 0;
}

.activity-info strong {
    color: var(--text-primary);
    font-size: 0.9rem;
}

.activity-info span {
    display: block;
    font-size: 0.85rem;
    color: var(--text-secondary);
    margin-top: 2px;
}

.activity-info p {
    margin: 4px 0 0;
    font-size: 0.8rem;
}

.activity-time {
    font-size: 0.78rem;
    color: var(--text-secondary);
    white-space: nowrap;
    flex-shrink: 0;
}

.text-muted { color: var(--text-secondary); font-size: 0.8rem; }

/* ============================================
   RESPONSIVE
   ============================================ */

@media (max-width: 1200px) {
    .admin-metrics { grid-template-columns: repeat(2, 1fr); }
    .admin-metrics.secondary { grid-template-columns: repeat(3, 1fr); }
    .admin-charts { grid-template-columns: 1fr; }
    .admin-grid-2 { grid-template-columns: 1fr; }
}

@media (max-width: 768px) {
    .admin-layout { flex-direction: column; }
    .admin-sidebar {
        width: 100%;
        height: auto;
        position: static;
        border-right: none;
        border-bottom: 1px solid var(--border-color);
    }
    .admin-nav {
        display: flex;
        overflow-x: auto;
        padding: 8px 12px;
        gap: 4px;
    }
    .admin-nav-item {
        padding: 10px 14px;
        white-space: nowrap;
        border-left: none;
        border-bottom: 3px solid transparent;
        font-size: 0.85rem;
    }
    .admin-nav-item.active { border-bottom-color: var(--neon-green); border-left: none; }
    .admin-main { padding: 20px 16px; }
    .admin-metrics { grid-template-columns: 1fr; }
    .admin-metrics.secondary { grid-template-columns: repeat(2, 1fr); }
    .admin-header { flex-direction: column; gap: 12px; }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
