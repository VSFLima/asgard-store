<?php
/**
 * Asgard Store - Painel do Usuario (Dashboard)
 */

$page_title = 'Meu Painel';
require_once __DIR__ . '/../includes/functions.php';
require_login();

$user_id = $_SESSION['user_id'];
$user = db_fetch("SELECT * FROM usuarios WHERE id = ?", [$user_id]);

// Estatisticas
$total_anuncios = db_count('anuncios', 'usuario_id = ?', [$user_id]);
$anuncios_aprovados = db_count('anuncios', 'usuario_id = ? AND status = ?', [$user_id, 'aprovado']);
$anuncios_pendentes = db_count('anuncios', 'usuario_id = ? AND status = ?', [$user_id, 'pendente']);
$anuncios_vendidos = db_count('anuncios', 'usuario_id = ? AND status = ?', [$user_id, 'vendido']);

$total_compras = db_count('compras', 'comprador_id = ?', [$user_id]);
$total_vendas = db_count('compras', 'vendedor_id = ?', [$user_id]);

// Compras e vendas pendentes
$compras_pendentes = db_count('compras', 'comprador_id = ? AND status IN (?, ?, ?)', [$user_id, 'aguardando_pagamento', 'pagamento_confirmado', 'em_entrega']);
$vendas_pendentes = db_count('compras', 'vendedor_id = ? AND status IN (?, ?, ?)', [$user_id, 'aguardando_pagamento', 'pagamento_confirmado', 'em_entrega']);

// Notificacoes nao lidas
$notificacoes_nao_lidas = db_count('notificacoes', 'usuario_id = ? AND lida = 0', [$user_id]);

// Anuncios recentes
$anuncios_recentes = db_fetch_all(
    "SELECT a.*, j.nome as jogo_nome 
     FROM anuncios a 
     JOIN jogos j ON a.jogo_id = j.id 
     WHERE a.usuario_id = ? 
     ORDER BY a.criado_em DESC LIMIT 5",
    [$user_id]
);

// Ultimas compras
$ultimas_compras = db_fetch_all(
    "SELECT c.*, a.titulo as anuncio_titulo, u.nome as vendedor_nome 
     FROM compras c 
     JOIN anuncios a ON c.anuncio_id = a.id 
     JOIN usuarios u ON c.vendedor_id = u.id 
     WHERE c.comprador_id = ? 
     ORDER BY c.criado_em DESC LIMIT 5",
    [$user_id]
);

// Ultimas vendas
$ultimas_vendas = db_fetch_all(
    "SELECT c.*, a.titulo as anuncio_titulo, u.nome as comprador_nome 
     FROM compras c 
     JOIN anuncios a ON c.anuncio_id = a.id 
     JOIN usuarios u ON c.comprador_id = u.id 
     WHERE c.vendedor_id = ? 
     ORDER BY c.criado_em DESC LIMIT 5",
    [$user_id]
);

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container" style="padding-top: 30px; padding-bottom: 60px;">
    <h1 class="section-title" style="margin-bottom: 10px;">
        <i class="fas fa-tachometer-alt" style="color: var(--neon-green);"></i> Meu Painel
    </h1>
    <p style="color: var(--text-muted); margin-bottom: 30px;">Bem-vindo, <?php echo sanitize($user['nome']); ?>!</p>

    <!-- Cards de Estatisticas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 30px;">
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem; color: var(--neon-green);"><i class="fas fa-ad"></i></div>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--text);"><?php echo $total_anuncios; ?></div>
            <div style="color: var(--text-muted); font-size: 0.85rem;">Meus Anuncios</div>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem; color: var(--neon-green);"><i class="fas fa-check-circle"></i></div>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--text);"><?php echo $anuncios_aprovados; ?></div>
            <div style="color: var(--text-muted); font-size: 0.85rem;">Aprovados</div>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem; color: var(--neon-blue);"><i class="fas fa-shopping-cart"></i></div>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--text);"><?php echo $total_compras; ?></div>
            <div style="color: var(--text-muted); font-size: 0.85rem;">Compras</div>
        </div>
        <div class="card" style="text-align: center; padding: 20px;">
            <div style="font-size: 2rem; color: var(--neon-green);"><i class="fas fa-dollar-sign"></i></div>
            <div style="font-size: 1.8rem; font-weight: 700; color: var(--neon-green);">R$ <?php echo number_format($user['saldo'], 2, ',', '.'); ?></div>
            <div style="color: var(--text-muted); font-size: 0.85rem;">Meu Saldo</div>
        </div>
    </div>

    <!-- Acoes Rapidas -->
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
        <a href="/painel/anuncio-novo.php" class="card" style="text-decoration: none; padding: 20px; text-align: center; transition: all 0.3s; border: 1px solid var(--neon-green);">
            <i class="fas fa-plus-circle" style="font-size: 1.5rem; color: var(--neon-green); margin-bottom: 10px; display: block;"></i>
            <span style="color: var(--text); font-weight: 600;">Criar Anuncio</span>
        </a>
        <a href="/painel/saldo.php" class="card" style="text-decoration: none; padding: 20px; text-align: center; transition: all 0.3s; border: 1px solid var(--neon-blue);">
            <i class="fas fa-wallet" style="font-size: 1.5rem; color: var(--neon-blue); margin-bottom: 10px; display: block;"></i>
            <span style="color: var(--text); font-weight: 600;">Ver Saldo</span>
        </a>
        <a href="/painel/compras.php" class="card" style="text-decoration: none; padding: 20px; text-align: center; transition: all 0.3s; border: 1px solid var(--neon-pink);">
            <i class="fas fa-shopping-bag" style="font-size: 1.5rem; color: var(--neon-pink); margin-bottom: 10px; display: block;"></i>
            <span style="color: var(--text); font-weight: 600;">Minhas Compras</span>
        </a>
        <a href="/loja/" class="card" style="text-decoration: none; padding: 20px; text-align: center; transition: all 0.3s; border: 1px solid var(--neon-purple);">
            <i class="fas fa-store" style="font-size: 1.5rem; color: var(--neon-purple); margin-bottom: 10px; display: block;"></i>
            <span style="color: var(--text); font-weight: 600;">Ir para Loja</span>
        </a>
    </div>

    <!-- Pendencias -->
    <?php if ($anuncios_pendentes > 0 || $vendas_pendentes > 0 || $compras_pendentes > 0): ?>
    <div class="card" style="margin-bottom: 30px; border-left: 3px solid var(--neon-yellow);">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-exclamation-triangle" style="color: var(--neon-yellow);"></i> Pendencias</h3>
        <div style="display: flex; flex-wrap: wrap; gap: 10px;">
            <?php if ($anuncios_pendentes > 0): ?>
                <a href="/painel/anuncios.php?status=pendente" style="background: rgba(255,193,7,0.1); border: 1px solid var(--neon-yellow); border-radius: 8px; padding: 8px 16px; color: var(--neon-yellow); text-decoration: none; font-size: 0.9rem;">
                    <?php echo $anuncios_pendentes; ?> anuncio(s) pendente(s)
                </a>
            <?php endif; ?>
            <?php if ($vendas_pendentes > 0): ?>
                <a href="/painel/vendas.php" style="background: rgba(0,123,255,0.1); border: 1px solid var(--neon-blue); border-radius: 8px; padding: 8px 16px; color: var(--neon-blue); text-decoration: none; font-size: 0.9rem;">
                    <?php echo $vendas_pendentes; ?> venda(s) pendente(s)
                </a>
            <?php endif; ?>
            <?php if ($compras_pendentes > 0): ?>
                <a href="/painel/compras.php" style="background: rgba(255,107,107,0.1); border: 1px solid var(--neon-pink); border-radius: 8px; padding: 8px 16px; color: var(--neon-pink); text-decoration: none; font-size: 0.9rem;">
                    <?php echo $compras_pendentes; ?> compra(s) pendente(s)
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- Anuncios Recentes -->
    <?php if (!empty($anuncios_recentes)): ?>
    <div class="card" style="margin-bottom: 30px;">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-ad" style="color: var(--neon-green);"></i> Meus Anuncios Recentes</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Anuncio</th>
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Jogo</th>
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Status</th>
                        <th style="text-align: right; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Preco</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($anuncios_recentes as $a): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 10px;">
                            <a href="/loja/anuncio.php?id=<?php echo $a['id']; ?>" style="color: var(--text); text-decoration: none;">
                                <?php echo sanitize($a['titulo']); ?>
                            </a>
                        </td>
                        <td style="padding: 10px; color: var(--text-muted);"><?php echo sanitize($a['jogo_nome']); ?></td>
                        <td style="padding: 10px;">
                            <?php
                            $status_class = match($a['status']) {
                                'aprovado' => 'color: var(--neon-green);',
                                'pendente' => 'color: var(--neon-yellow);',
                                'reprovado' => 'color: var(--neon-pink);',
                                'vendido' => 'color: var(--neon-blue);',
                                default => 'color: var(--text-muted);'
                            };
                            ?>
                            <span style="<?php echo $status_class; ?> font-size: 0.85rem; font-weight: 600;">
                                <?php echo ucfirst($a['status']); ?>
                            </span>
                        </td>
                        <td style="padding: 10px; text-align: right; color: var(--neon-green); font-weight: 600;">
                            R$ <?php echo number_format($a['preco'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-top: 15px;">
            <a href="/painel/anuncios.php" style="color: var(--neon-green); text-decoration: none; font-size: 0.9rem;">Ver todos →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ultimas Vendas -->
    <?php if (!empty($ultimas_vendas)): ?>
    <div class="card" style="margin-bottom: 30px;">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-hand-holding-usd" style="color: var(--neon-green);"></i> Ultimas Vendas</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Anuncio</th>
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Comprador</th>
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Status</th>
                        <th style="text-align: right; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimas_vendas as $v): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 10px; color: var(--text);"><?php echo sanitize($v['anuncio_titulo']); ?></td>
                        <td style="padding: 10px; color: var(--text-muted);"><?php echo sanitize($v['comprador_nome']); ?></td>
                        <td style="padding: 10px;">
                            <?php
                            $status_labels = [
                                'aguardando_pagamento' => ['Aguardando PG', 'var(--neon-yellow)'],
                                'pagamento_confirmado' => ['PG Confirmado', 'var(--neon-blue)'],
                                'em_entrega' => ['Em Entrega', 'var(--neon-purple)'],
                                'concluido' => ['Concluido', 'var(--neon-green)'],
                                'cancelado' => ['Cancelado', 'var(--neon-pink)'],
                                'disputa' => ['Em Disputa', 'var(--neon-pink)'],
                            ];
                            $label = $status_labels[$v['status']] ?? ['Desconhecido', 'var(--text-muted)'];
                            ?>
                            <span style="color: <?php echo $label[1]; ?>; font-size: 0.85rem; font-weight: 600;">
                                <?php echo $label[0]; ?>
                            </span>
                        </td>
                        <td style="padding: 10px; text-align: right; color: var(--neon-green); font-weight: 600;">
                            R$ <?php echo number_format($v['valor'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-top: 15px;">
            <a href="/painel/vendas.php" style="color: var(--neon-green); text-decoration: none; font-size: 0.9rem;">Ver todas →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Ultimas Compras -->
    <?php if (!empty($ultimas_compras)): ?>
    <div class="card" style="margin-bottom: 30px;">
        <h3 style="margin-bottom: 15px;"><i class="fas fa-shopping-cart" style="color: var(--neon-blue);"></i> Minhas Ultimas Compras</h3>
        <div style="overflow-x: auto;">
            <table style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Anuncio</th>
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Vendedor</th>
                        <th style="text-align: left; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Status</th>
                        <th style="text-align: right; padding: 10px; color: var(--text-muted); font-size: 0.85rem;">Valor</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ultimas_compras as $c): ?>
                    <tr style="border-bottom: 1px solid var(--border-color);">
                        <td style="padding: 10px; color: var(--text);"><?php echo sanitize($c['anuncio_titulo']); ?></td>
                        <td style="padding: 10px; color: var(--text-muted);"><?php echo sanitize($c['vendedor_nome']); ?></td>
                        <td style="padding: 10px;">
                            <?php
                            $label = $status_labels[$c['status']] ?? ['Desconhecido', 'var(--text-muted)'];
                            ?>
                            <span style="color: <?php echo $label[1]; ?>; font-size: 0.85rem; font-weight: 600;">
                                <?php echo $label[0]; ?>
                            </span>
                        </td>
                        <td style="padding: 10px; text-align: right; color: var(--neon-green); font-weight: 600;">
                            R$ <?php echo number_format($c['valor'], 2, ',', '.'); ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div style="text-align: center; margin-top: 15px;">
            <a href="/painel/compras.php" style="color: var(--neon-green); text-decoration: none; font-size: 0.9rem;">Ver todas →</a>
        </div>
    </div>
    <?php endif; ?>

    <!-- Perfil incompleto -->
    <?php if (empty($user['chave_pix']) || empty($user['telefone'])): ?>
    <div class="card" style="margin-bottom: 30px; border-left: 3px solid var(--neon-yellow);">
        <h3 style="margin-bottom: 10px;"><i class="fas fa-user-exclamation" style="color: var(--neon-yellow);"></i> Complete seu Perfil</h3>
        <p style="color: var(--text-muted); margin-bottom: 15px;">Adicione seus dados de pagamento e contato para poder vender e receber.</p>
        <a href="/painel/perfil.php" class="btn btn-primary"><i class="fas fa-edit"></i> Completar Perfil</a>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>