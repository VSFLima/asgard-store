<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$files_to_delete = [
    'diagnose.php',
    'setup.php',
    'test-login.php',
    'fix-admin.php',
    'cleanup.php'
];

echo "=== CLEANING UP DIAGNOSTIC FILES ===\n\n";

foreach ($files_to_delete as $file) {
    $path = __DIR__ . '/' . $file;
    if (file_exists($path)) {
        if (unlink($path)) {
            echo "DELETED: $file\n";
        } else {
            echo "FAILED to delete: $file\n";
        }
    } else {
        echo "NOT FOUND: $file\n";
    }
}

echo "\n=== CLEANUP COMPLETE ===\n";
echo "This page will now self-delete. Refresh to confirm.\n";

// Self-delete
unlink(__FILE__);
?>
