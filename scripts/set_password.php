<?php

require __DIR__.'/../vendor/autoload.php';
/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

if ($argc < 3) {
    fwrite(STDERR, "Usage: php scripts/set_password.php <email> <new_password>\n");
    exit(1);
}

[$script, $email, $new] = $argv;
$updated = DB::table('users')->where('email', $email)->update([
    'password' => Hash::make($new),
    'updated_at' => now(),
]);

echo $updated ? "UPDATED\n" : "NO_USER\n";
