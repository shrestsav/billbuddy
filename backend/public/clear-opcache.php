<?php
// Clear OPcache
if (function_exists('opcache_reset')) {
    $result = opcache_reset();
    echo json_encode(['success' => $result, 'message' => 'OPcache cleared']);
} else {
    echo json_encode(['success' => false, 'message' => 'OPcache not available']);
}
