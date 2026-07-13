<?php
/**
 * Asgard Store - Testes Unitarios das Funcoes Principais
 * Execute: php tests/test_functions.php
 */

require_once __DIR__ . '/../includes/functions.php';

$passed = 0;
$failed = 0;

function test($name, $condition) {
    global $passed, $failed;
    if ($condition) {
        echo "  ✅ {$name}\n";
        $passed++;
    } else {
        echo "  ❌ {$name}\n";
        $failed++;
    }
}

echo "\n🧪 Testes do Asgard Store\n";
echo str_repeat('=', 50) . "\n";

// === SEGURANCA ===
echo "\n🔒 SEGURANCA\n";

test('hash_password retorna hash valido', 
    password_verify('teste123', hash_password('teste123')));

test('verify_password correta retorna true', 
    verify_password('teste123', hash_password('teste123')));

test('verify_password incorreta retorna false', 
    !verify_password('errada', hash_password('teste123')));

test('sanitize remove HTML basico', 
    sanitize('<script>alert(1)</script>') !== '<script>alert(1)</script>');

test('sanitize converte entidades HTML', 
    sanitize !==('<b>'), '&lt;b&gt;') || sanitize !==('<b>'), '&lt;'));

test('slugify gera slug valido', 
    preg_match('/^[a-z0-9-]+$/', slugify('Meu Teste Legal!')));

test('slugify normaliza acentos', 
    slugify('São Paulo') === 'sao-paulo');

// === FORMATTING ===
echo "\n💰 FORMATTACAO\n";

test('format_money retorna formato BRL', 
    strpos(format_money(1234.56), '1.234,56'));

test('format_money com zero', 
    strpos(format_money(0), '0,00'));

test('time_ago retorna string', 
    is_string(time_ago(date('Y-m-d H:i:s'))));

// === PAGINACAO ===
echo "\n📄 PAGINACAO\n";

$pagination = paginate(100, 20, 1);
test('paginate pagina 1 - offset 0', $pagination['offset'] === 0);
test('paginate pagina 1 - total_pages 5', $pagination['total_pages'] === 5);
test('paginate pagina 1 - tem anterior false', $pagination['has_prev'] === false);
test('paginate pagina 1 - tem proximo true', $pagination['has_next'] === true);

$pagination2 = paginate(100, 20, 5);
test('paginate ultima pagina - tem proximo false', $pagination2['has_next'] === false);
test('paginate ultima pagina - tem anterior true', $pagination2['has_prev'] === true);

// === CSRF ===
echo "\n🛡️ CSRF\n";

session_start();
$token = generate_csrf_token();
test('generate_csrf_token retorna string', is_string($token));
test('generate_csrf_token nao esta vazio', !empty($token));
test('validate_csrf_token com token valido retorna true', validate_csrf_token($token));
test('validate_csrf_token com token invalido retorna false', !validate_csrf_token('token_invalido_123'));

$field = csrf_field();
test('csrf_field retorna input hidden', strpos($field, 'type="hidden"'));
test('csrf_field contem token', strpos($field, $token));

// === SLUGIFY EDGE CASES ===
echo "\n📝 SLUGIFY\n";

test('slugify string vazia', slugify('') === '');
test('slugify so espacos', slugify('   ') === '');
test('slugify com caracteres especiais', 
    !strpos(slugify('Teste! @#\$%'), '!'));
test('slugify remove duplos hifens', 
    !strpos(slugify('a--b'), '--'));

// === RESUMO ===
echo "\n" . str_repeat('=', 50) . "\n";
echo "📊 Resultado: {$passed} passou";
if ($failed > 0) {
    echo ", {$failed} falhou";
}
echo "\n";
echo ($failed === 0 ? "\n🎉 TODOS OS TESTES PASSARAM!\n\n" : "\n⚠️ ALGUNS TESTES FALHARAM\n\n");
exit($failed > 0 ? 1 : 0);
