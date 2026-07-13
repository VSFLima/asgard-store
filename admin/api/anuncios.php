<?php
/**
 * Asgard Store - API Admin: Acoes rapidas em anuncios
 * POST: aprovar, rejeitar, excluir
 */

require_once __DIR__ . '/../../includes/functions.php';
require_admin();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_error('Metodo nao permitido', 405);
}

require_csrf();

$acao = $_POST['acao'] ?? '';
$anuncio_id = intval($_POST['anuncio_id'] ?? 0);

if ($anuncio_id <= 0) {
    json_error('Anuncio invalido');
}

$anuncio = db_fetch("SELECT * FROM anuncios WHERE id = ?", [$anuncio_id]);
if (!$anuncio) {
    json_error('Anuncio nao encontrado');
}

$admin_id = $_SESSION['user_id'];

switch ($acao) {
    case 'aprovar':
        db_update('anuncios', ['status' => 'aprovado', 'motivo_reprovacao' => null], 'id = ?', [$anuncio_id]);
        create_notification($anuncio['usuario_id'], 'Anuncio Aprovado', "Seu anuncio \"{$anuncio['titulo']}\" foi aprovado!", 'sistema', "/loja/anuncio.php?id={$anuncio_id}");
        db_insert('admin_log', ['admin_id' => $admin_id, 'acao' => 'aprovar_anuncio', 'descricao' => "Anuncio #{$anuncio_id} aprovado", 'tipo' => 'anuncios']);
        json_success(['status' => 'aprovado'], 'Anuncio aprovado');
        break;

    case 'rejeitar':
        $motivo = sanitize($_POST['motivo'] ?? '');
        if (empty($motivo)) {
            json_error('Motivo e obrigatorio para reprovacao');
        }
        db_update('anuncios', ['status' => 'reprovado', 'motivo_reprovacao' => $motivo], 'id = ?', [$anuncio_id]);
        create_notification($anuncio['usuario_id'], 'Anuncio Reprovado', "Seu anuncio \"{$anuncio['titulo']}\" foi reprovado. Motivo: {$motivo}", 'sistema', "/painel/anuncio-editar.php?id={$anuncio_id}");
        db_insert('admin_log', ['admin_id' => $admin_id, 'acao' => 'rejeitar_anuncio', 'descricao' => "Anuncio #{$anuncio_id} reprovado", 'tipo' => 'anuncios']);
        json_success(['status' => 'reprovado'], 'Anuncio reprovado');
        break;

    case 'excluir':
        db_delete('anuncios', 'id = ?', [$anuncio_id]);
        db_insert('admin_log', ['admin_id' => $admin_id, 'acao' => 'excluir_anuncio', 'descricao' => "Anuncio #{$anuncio_id} excluido", 'tipo' => 'anuncios']);
        json_success([], 'Anuncio excluido');
        break;

    default:
        json_error('Acao invalida');
}
