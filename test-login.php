<?php
/**
 * Asgard Store - Test Admin Login
 */
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
    
    // Check admin user
    $stmt = $pdo->prepare('SELECT id, nome, sobrenome, email, admin, status, senha FROM usuarios WHERE email = ?');
    $stmt->execute(['admin@asgard.store']);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "Admin user found!\n";
        echo "ID: {$user['id']}\n";
        echo "Name: {$user['nome']} {$user['sobrenome']}\n";
        echo "Email: {$user['email']}\n";
        echo "Admin: {$user['admin']}\n";
        echo "Status: {$user['status']}\n";
        
        // Test password
        if (password_verify('admin123', $user['senha'])) {
            echo "\nPassword 'admin123' is VALID!\n";
        } else {
            echo "\nPassword 'admin123' is INVALID!\n";
            echo "Hash: {$user['senha']}\n";
            // Reset password
            $newHash = password_hash('admin123', PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE usuarios SET senha = ? WHERE id = ?')->execute([$newHash, $user['id']]);
            echo "Password RESET to 'admin123'\n";
        }
    } else {
        echo "Admin user NOT FOUND! Creating...\n";
        $hash = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare('INSERT INTO usuarios (nome, sobrenome, email, senha, admin, status) VALUES (?, ?, ?, ?, 1, "ativo")')
             ->execute(['Admin', 'Asgard Store', 'admin@asgard.store', $hash]);
        echo "Admin created! Email: admin@asgard.store / Pass: admin123\n";
    }
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
