<?php

require __DIR__.'/../vendor/autoload.php';
/** @var \Illuminate\Foundation\Application $app */
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

$email = $argv[1] ?? 'kankerry@gmail.com';
$plain = $argv[2] ?? 'visitek19';
$name = $argv[3] ?? 'Super Admin';

$user = User::where('email', $email)->first();
if (! $user) {
    $user = User::create([
        'name' => $name,
        'email' => $email,
        'password' => Hash::make($plain),
        'koperasi_id' => null,
    ]);
    echo "USER_CREATED\n";
} else {
    $user->name = $name;
    $user->password = Hash::make($plain);
    $user->save();
    echo "USER_UPDATED\n";
}

$role = Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
if (! $user->hasRole('superadmin')) {
    $user->assignRole($role);
    echo "ROLE_ASSIGNED\n";
} else {
    echo "ROLE_EXISTS\n";
}

echo "DONE: {$email} -> superadmin\n";

