<?php
/**
 * Asgard Store - API Publica: Jogos
 * GET: listar jogos ativos
 */

require_once __DIR__ . '/../../includes/functions.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Metodo nao permitido', 405);
}

$jogos = db_fetch_all(
    "SELECT j.id, j.nome, j.slug, j.icone, j.moeda_nome, j.moeda_icone,
            (SELECT COUNT(*) FROM anuncios WHERE jogo_id = j.id AND status = 'aprovado') as total_anuncios
     FROM jogos j
     WHERE j.ativo = 1
     ORDER BY j.ordem ASC, j.nome ASC"
);

json_success(['data' => $jogos, 'total' => count($jogos)]);
