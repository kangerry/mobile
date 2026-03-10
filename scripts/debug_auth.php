<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

$email = $argv[1] ?? null;
$plain = $argv[2] ?? null;

$model = Config::get('auth.providers.users.model');
$conn = DB::connection();
$driver = $conn->getDriverName();
$dbName = $conn->getDatabaseName();
echo "MODEL={$model}\n";
echo "DB={$driver}:{$dbName}\n";

if ($email) {
    $user = DB::table('users')->where('email', strtolower(trim($email)))->first();
    echo $user ? "USER=FOUND\n" : "USER=NOT_FOUND\n";
    if ($user && $plain) {
        echo Hash::check($plain, $user->password) ? "PASSWORD=OK\n" : "PASSWORD=FAIL\n";
    }
}

