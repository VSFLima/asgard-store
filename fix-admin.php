<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
} else {
    die('config.local.php not found!');
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "=== FIXING ADMIN EMAIL ===\n";
    
    // Fix email from underscore to dot
    $stmt = $pdo->prepare('UPDATE usuarios SET email = ? WHERE email = ?');
    $stmt->execute(['admin@asgard.store', 'admin@asgard_store.com']);
    echo "Rows updated: " . $stmt->rowCount() . "\n";
    
    // Reset password
    $hash = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE usuarios SET senha = ? WHERE email = ?');
    $stmt->execute([$hash, 'admin@asgard.store']);
    echo "Password reset to admin123\n";
    
    // Verify
    $admin = $pdo->query('SELECT id, nome, email, admin, status FROM usuarios WHERE admin = 1')->fetch(PDO::FETCH_ASSOC);
    echo "\nAdmin account:\n";
    echo "  ID: {$admin['id']}\n";
    echo "  Name: {$admin['nome']}\n";
    echo "  Email: {$admin['email']}\n";
    echo "  Admin: {$admin['admin']}\n";
    echo "  Status: {$admin['status']}\n";
    
    // Test password
    $stmt = $pdo->prepare('SELECT senha FROM usuarios WHERE email = ?');
    $stmt->execute(['admin@asgard.store']);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (password_verify('admin123', $row['senha'])) {
        echo "\nPassword 'admin123' VERIFIED OK!\n";
    }
    
    // Count tables with data
    echo "\n=== DATABASE STATUS ===\n";
    $tables = ['usuarios', 'jogos', 'categorias', 'anuncios', 'creditos', 'configuracoes', 'redes_sociais'];
    foreach ($tables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "  $t: $count rows\n";
    }
    
    echo "\n=== ADMIN FIXED ===";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
