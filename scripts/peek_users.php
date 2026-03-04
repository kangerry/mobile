<?php

require __DIR__.'/../vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

try {
    $users = DB::table('users')->select('id', 'name', 'email')->get();
    echo json_encode(['count' => $users->count(), 'users' => $users], JSON_PRETTY_PRINT).PHP_EOL;
} catch (Throwable $e) {
    echo 'ERR: '.$e->getMessage().PHP_EOL;
    exit(1);
}
