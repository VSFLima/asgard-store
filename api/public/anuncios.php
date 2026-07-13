<?php
/**
 * Asgard Store - API Publica: Anuncios
 * GET: listar aprovados, buscar por id/jogo
 */

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Metodo nao permitido', 405);
}

$page = max(1, intval($_GET['page'] ?? 1));
$per_page = min(50, max(1, intval($_GET['per_page'] ?? 20)));
$jogo_id = intval($_GET['jogo_id'] ?? 0);
$busca = sanitize($_GET['q'] ?? '');
$preco_min = floatval($_GET['preco_min'] ?? 0);
$preco_max = floatval($_GET['preco_max'] ?? 0);
$ordenar = $_GET['ordenar'] ?? 'recente';
$anuncio_id = intval($_GET['id'] ?? 0);

// Buscar anuncio especifico
if ($anuncio_id > 0) {
    $anuncio = db_fetch(
        "SELECT a.id, a.titulo, a.descricao, a.preco, a.nivel_rank, a.servidor, 
                a.itens_especiais, a.screenshots, a.video_url, a.visualizacoes, a.criado_em,
                j.nome as jogo_nome, j.slug as jogo_slug, j.icone as jogo_icone,
                u.id as vendedor_id, u.nome as vendedor_nome, u.nota_media, u.total_vendas,
                u.admin as vendedor_admin
         FROM anuncios a
         JOIN jogos j ON a.jogo_id = j.id
         JOIN usuarios u ON a.usuario_id = u.id
         WHERE a.id = ? AND a.status = 'aprovado'",
        [$anuncio_id]
    );

    if (!$anuncio) {
        json_error('Anuncio nao encontrado', 404);
    }

    // Incrementar visualizacoes
    db_query("UPDATE anuncios SET visualizacoes = visualizacoes + 1 WHERE id = ?", [$anuncio_id]);

    $anuncio['screenshots'] = json_decode($anuncio['screenshots'] ?? '[]', true) ?: [];
    json_success($anuncio);
    exit;
}

// Listar anuncios
$where = "a.status = 'aprovado'";
$params = [];

if ($jogo_id > 0) {
    $where .= " AND a.jogo_id = ?";
    $params[] = $jogo_id;
}

if ($busca) {
    $where .= " AND (a.titulo LIKE ? OR a.descricao LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($preco_min > 0) {
    $where .= " AND a.preco >= ?";
    $params[] = $preco_min;
}

if ($preco_max > 0) {
    $where .= " AND a.preco <= ?";
    $params[] = $preco_max;
}

$order = match($ordenar) {
    'preco_asc' => 'a.preco ASC',
    'preco_desc' => 'a.preco DESC',
    'popular' => 'a.visualizacoes DESC',
    default => 'a.criado_em DESC',
};

$total = db_count('anuncios a', $where, $params);
$pagination = paginate($total, $per_page, $page);

$anuncios = db_fetch_all(
    "SELECT a.id, a.titulo, a.preco, a.screenshots, a.visualizacoes, a.criado_em,
            j.nome as jogo_nome, j.slug as jogo_slug, j.icone as jogo_icone,
            u.nome as vendedor_nome, u.nota_media, u.admin as vendedor_admin
     FROM anuncios a
     JOIN jogos j ON a.jogo_id = j.id
     JOIN usuarios u ON a.usuario_id = u.id
     WHERE {$where}
     ORDER BY {$order}
     LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}",
    $params
);

foreach ($anuncios as &$a) {
    $a['screenshots'] = json_decode($a['screenshots'] ?? '[]', true) ?: [];
}

json_success([
    'data' => $anuncios,
    'pagination' => $pagination,
    'total' => $total,
]);
