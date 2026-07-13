<?php
/**
 * Asgard Store - API Admin: Dados dos graficos do dashboard
 * GET: vendas_por_dia, faturamento_por_dia, anuncios_por_status, novos_usuarios_por_dia
 */

require_once __DIR__ . '/../../includes/functions.php';
require_admin();

header('Content-Type: application/json');

$tipo = $_GET['tipo'] ?? 'vendas_por_dia';
$dias = min(30, max(7, intval($_GET['dias'] ?? 7)));

switch ($tipo) {
    case 'vendas_por_dia':
        $dados = db_fetch_all(
            "SELECT DATE(criado_em) as data, COUNT(*) as total 
             FROM compras 
             WHERE criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY) AND status = 'concluido'
             GROUP BY DATE(criado_em) ORDER BY data ASC",
            [$dias]
        );
        json_success(['labels' => array_column($dados, 'data'), 'values' => array_map('intval', array_column($dados, 'total'))]);
        break;

    case 'faturamento_por_dia':
        $dados = db_fetch_all(
            "SELECT DATE(criado_em) as data, SUM(comissao) as total 
             FROM compras 
             WHERE criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY) AND status = 'concluido'
             GROUP BY DATE(criado_em) ORDER BY data ASC",
            [$dias]
        );
        json_success(['labels' => array_column($dados, 'data'), 'values' => array_map('floatval', array_column($dados, 'total'))]);
        break;

    case 'anuncios_por_status':
        $dados = db_fetch_all(
            "SELECT status, COUNT(*) as total FROM anuncios GROUP BY status"
        );
        json_success(['labels' => array_column($dados, 'status'), 'values' => array_map('intval', array_column($dados, 'total'))]);
        break;

    case 'novos_usuarios_por_dia':
        $dados = db_fetch_all(
            "SELECT DATE(criado_em) as data, COUNT(*) as total 
             FROM usuarios 
             WHERE criado_em >= DATE_SUB(NOW(), INTERVAL ? DAY)
             GROUP BY DATE(criado_em) ORDER BY data ASC",
            [$dias]
        );
        json_success(['labels' => array_column($dados, 'data'), 'values' => array_map('intval', array_column($dados, 'total'))]);
        break;

    default:
        json_error('Tipo de dado invalido');
}
