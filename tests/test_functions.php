<?php
/**
 * Asgard Store - Testes Unitarios das Funcoes Principais
 * Execute: php tests/test_functions.php
 * Compativel com PHP 7.4+
 */

require_once __DIR__ . '/../includes/functions.php';

$passed = 0;
$failed = 0;

function test($name, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  PASS: {$name}\n";
        $passed++;
    } else {
        echo "  FAIL: {$name}\n";
        $failed++;
    }
}

echo "\n=== TESTES ASGARD STORE ===\n";

echo "\nSEGURANCA:\n";
test('hash_password retorna hash valido', password_verify('teste123', hash_password('teste123')));
test('verify_password correta retorna true', verify_password('teste123', hash_password('teste123')));
test('verify_password incorreta retorna false', !verify_password('errada', hash_password('teste123')));
test('sanitize remove script tag', strpos(sanitize('<script>alert(1)</script>'), '<script>') === false);
test('slugify gera slug valido', preg_match('/^[a-z0-9-]+$/', slugify('Meu Teste Legal!')));
test('slugify normaliza acentos', slugify('Sao Paulo') === 'sao-paulo');

echo "\nFORMATTACAO:\n";
test('format_money retorna formato BRT', strpos(format_money(1234.56), '1.234,56') !== false);
test('format_money com zero', strpos(format_money(0), '0,00') !== false);
test('time_ago retorna string', is_string(time_ago(date('Y-m-d H:i:s'))));

echo "\nPAGINACAO:\n";
$pagination = paginate(100, 20, 1);
test('paginate pag1 offset 0', $pagination['offset'] === 0);
test('paginate pag1 total_pages 5', $pagination['total_pages'] === 5);
test('paginate pag1 has_prev false', $pagination['has_prev'] === false);
test('paginate pag1 has_next true', $pagination['has_next'] === true);
$pagination2 = paginate(100, 20, 5);
test('paginate last page has_next false', $pagination2['has_next'] === false);
test('paginate last page has_prev true', $pagination2['has_prev'] === true);

echo "\nCSRF:\n";
$token = generate_csrf_token();
test('generate_csrf_token retorna string', is_string($token));
test('generate_csrf_token nao vazio', !empty($token));
test('validate_csrf_token valido retorna true', validate_csrf_token($token));
test('validate_csrf_token invalido retorna false', !validate_csrf_token('token_invalido_123'));
$field = csrf_field();
test('csrf_field retorna input hidden', strpos($field, 'type="hidden"') !== false);
test('csrf_field contem token', strpos($field, $token) !== false);

echo "\nSLUGIFY EDGE CASES:\n";
test('slugify string vazia', slugify('') === '');
test('slugify so espacos', slugify('   ') === '');
test('slugify remove caracteres especiais', strpos(slugify('Teste! @#\$%'), '!') === false);
test('slugify remove duplos hifens', strpos(slugify('a--b'), '--') === false);

echo "\n=== RESULTADO ===\n";
echo "Passou: {$passed}\n";
if ($failed > 0) { echo "Falhou: {$failed}\n"; }
echo ($failed === 0 ? "\nTODOS OS TESTES PASSARAM!\n" : "\nALGUNS TESTES FALHARAM\n");
exit($failed > 0 ? 1 : 0);
