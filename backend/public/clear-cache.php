<?php
// Clear OPcache
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    echo "OPcache reset: " . ($result ? "success" : "failed") . "\n";
} else {
    echo "OPcache not available\n";
}

// Clear Laravel caches
chdir(dirname(__DIR__));
echo shell_exec('php artisan config:clear 2>&1') . "\n";
echo shell_exec('php artisan route:clear 2>&1') . "\n";
echo shell_exec('php artisan cache:clear 2>&1') . "\n";
echo "Done!\n";
