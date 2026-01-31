<?php
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo json_encode(['status' => 'OPcache cleared']);
} else {
    echo json_encode(['status' => 'OPcache not available']);
}
