<?php
// Asgard Store - Diagnostic Page
error_reporting(E_ALL);
ini_set('display_errors', 1);

if (php_sapi_name() === 'cli') {
    die('Run this in a browser, not CLI');
}
?>
<!DOCTYPE html>
<html><head><meta charset="utf-8"><title>Asgard Store - Diagnostico</title>
<style>
body { background: #1a1a2e; color: #00ff88; font-family: monospace; padding: 20px; }
h1 { color: #00d4ff; }
.ok { color: #00ff88; } .err { color: #ff4444; } .warn { color: #ffd700; }
pre { background: #0d0d1a; padding: 15px; border-radius: 8px; overflow-x: auto; }
.card { background: #16213e; border: 1px solid #00ff8833; padding: 20px; margin: 15px 0; border-radius: 10px; }
</style></head><body>
<h1>🔧 Asgard Store - Diagnostico</h1>

<div class="card">
<h2>1. PHP Info</h2>
<pre>
<?php
echo "PHP Version: " . phpversion() . "\n";
echo "Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'N/A') . "\n";
echo "Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'N/A') . "\n";
echo "Script Filename: " . ($_SERVER['SCRIPT_FILENAME'] ?? 'N/A') . "\n";
echo "Extensions: " . implode(', ', get_loaded_extensions()) . "\n";
echo "PDO Drivers: " . implode(', ', PDO::getAvailableDrivers()) . "\n";
?>
</pre>
</div>

<div class="card">
<h2>2. Config Files</h2>
<pre>
<?php
// Test config.php
if (file_exists(__DIR__ . '/config.php')) {
    echo "<span class='ok'>config.php: EXISTS</span>\n";
} else {
    echo "<span class='err'>config.php: NOT FOUND</span>\n";
}

// Test config.local.php
if (file_exists(__DIR__ . '/config.local.php')) {
    echo "<span class='ok'>config.local.php: EXISTS</span>\n";
    require_once __DIR__ . '/config.php';
    echo "DB_HOST: " . (defined('DB_HOST') ? DB_HOST : 'NOT DEFINED') . "\n";
    echo "DB_NAME: " . (defined('DB_NAME') ? DB_NAME : 'NOT DEFINED') . "\n";
    echo "DB_USER: " . (defined('DB_USER') ? DB_USER : 'NOT DEFINED') . "\n";
    echo "DB_PASS: " . (defined('DB_PASS') ? '****' : 'NOT DEFINED') . "\n";
    echo "SITE_URL: " . (defined('SITE_URL') ? SITE_URL : 'NOT DEFINED') . "\n";
    echo "DEBUG_MODE: " . (defined('DEBUG_MODE') ? (DEBUG_MODE ? 'true' : 'false') : 'NOT DEFINED') . "\n";
} else {
    echo "<span class='err'>config.local.php: NOT FOUND - THIS IS THE PROBLEM!</span>\n";
}
?>
</pre>
</div>

<div class="card">
<h2>3. Database Connection</h2>
<pre>
<?php
if (!file_exists(__DIR__ . '/config.local.php')) {
    echo "<span class='err'>Cannot test DB - config.local.php not found</span>\n";
} else {
    require_once __DIR__ . '/config.php';
    try {
        $pdo = new PDO(
            'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
            DB_USER, DB_PASS,
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        echo "<span class='ok'>CONNECTED to MySQL!</span>\n";
        
        // List tables
        $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
        echo "Tables found: " . count($tables) . "\n";
        foreach ($tables as $t) {
            echo "  - $t\n";
        }
        
        // Check users
        $users = $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn();
        echo "\nUsers: $users\n";
        
        // Check games
        $games = $pdo->query('SELECT COUNT(*) FROM jogos')->fetchColumn();
        echo "Games: $games\n";
        
        // Check admin
        $admin = $pdo->query("SELECT id, nome, email, admin FROM usuarios WHERE admin = 1")->fetchAll(PDO::FETCH_ASSOC);
        echo "Admins: " . count($admin) . "\n";
        foreach ($admin as $a) {
            echo "  - ID:{$a['id']} Name:{$a['nome']} Email:{$a['email']}\n";
        }
        
    } catch (PDOException $e) {
        echo "<span class='err'>DB ERROR: " . $e->getMessage() . "</span>\n";
    }
}
?>
</pre>
</div>

<div class="card">
<h2>4. File Structure</h2>
<pre>
<?php
$dirs = ['admin', 'api', 'auth', 'creditos', 'includes', 'loja', 'pages', 'painel', 'sql', 'suporte', 'assets/css', 'assets/js', 'assets/img'];
foreach ($dirs as $d) {
    $path = __DIR__ . '/' . $d;
    if (is_dir($path)) {
        $files = glob($path . '/*');
        echo "<span class='ok'>✓ $d/</span> (" . count($files) . " files)\n";
    } else {
        echo "<span class='err'>✗ $d/ NOT FOUND</span>\n";
    }
}
?>
</pre>
</div>

<div class="card">
<h2>5. Quick Actions</h2>
<pre>
<?php
if (file_exists(__DIR__ . '/config.local.php')) {
    require_once __DIR__ . '/config.php';
    require_once __DIR__ . '/includes/functions.php';
    echo "functions.php loaded OK\n";
    
    // Test DB functions
    if (function_exists('db_fetch_all')) {
        echo "<span class='ok'>db_fetch_all() available</span>\n";
    }
    if (function_exists('db_count')) {
        echo "<span class='ok'>db_count() available</span>\n";
    }
    if (function_exists('sanitize')) {
        echo "<span class='ok'>sanitize() available</span>\n";
    }
    if (function_exists('format_money')) {
        echo "<span class='ok'>format_money() available</span>\n";
    }
    if (function_exists('time_ago')) {
        echo "<span class='ok'>time_ago() available</span>\n";
    }
}
?>
</pre>
</div>

<hr>
<p style="color: #666;">Asgard Store Diagnostic v1.0 | <?php echo date('Y-m-d H:i:s'); ?></p>
</body></html>
