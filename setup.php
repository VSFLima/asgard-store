<?php
/**
 * Asgard Store - Auto Setup Script
 * Run once to create database tables
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.local.php';
} else {
    die('ERROR: config.local.php not found!');
}

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "Connected to MySQL!\n\n";
    
    // Read and execute schema
    $schema = file_get_contents(__DIR__ . '/sql/schema.sql');
    if ($schema === false) {
        die('ERROR: sql/schema.sql not found!');
    }
    
    // Split by semicolons and execute each query
    $queries = array_filter(array_map('trim', explode(';', $schema)));
    $executed = 0;
    $errors = 0;
    
    foreach ($queries as $query) {
        if (empty($query) || strpos($query, '--') === 0) continue;
        try {
            $pdo->exec($query);
            $executed++;
        } catch (PDOException $e) {
            // Skip duplicate key errors
            if ($e->getCode() != 23000) {
                echo "Error: " . $e->getMessage() . "\n";
                $errors++;
            }
        }
    }
    
    echo "Queries executed: $executed\n";
    echo "Errors: $errors\n\n";
    
    // Verify tables
    $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables created: " . count($tables) . "\n";
    foreach ($tables as $t) {
        $count = $pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
        echo "  - $t ($count rows)\n";
    }
    
    echo "\n=== SETUP COMPLETE ===";
    
} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage() . "\n";
}
?>
