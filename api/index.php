<?php

// Vercel: filesystem read-only, hanya /tmp yang boleh ditulis.
$storage = '/tmp/storage';
foreach (['app/public', 'framework/cache/data', 'framework/sessions', 'framework/views', 'logs'] as $dir) {
    if (! is_dir("$storage/$dir")) {
        @mkdir("$storage/$dir", 0777, true);
    }
}

define('LARAVEL_START', microtime(true));

require __DIR__ . '/../vendor/autoload.php';

$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->useStoragePath($storage);

$app->handleRequest(Illuminate\Http\Request::capture());