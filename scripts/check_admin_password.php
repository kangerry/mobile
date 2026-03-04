<?php

require __DIR__.'/../vendor/autoload.php';
/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$email = $argv[1] ?? 'admin@komera.local';
$plain = $argv[2] ?? 'admin12345';

$user = DB::table('users')->where('email', $email)->first();
if (! $user) {
    echo "NO_USER\n";
    exit(1);
}

echo Hash::check($plain, $user->password) ? "OK\n" : "FAIL\n";
