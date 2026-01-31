<?php
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$content = json_encode([
    'name' => 'PHP Test',
    'email' => 'phptest' . time() . '@example.com',
    'password' => 'Password123!',
    'password_confirmation' => 'Password123!'
]);

$request = Illuminate\Http\Request::create('/api/auth/register', 'POST', [], [], [], [
    'CONTENT_TYPE' => 'application/json',
    'HTTP_ACCEPT' => 'application/json',
], $content);

$response = $kernel->handle($request);
echo $response->getContent();
