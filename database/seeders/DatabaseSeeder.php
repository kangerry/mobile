<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Modules\Core\Database\Seeders\CoreDatabaseSeeder;
use Modules\Dompet\Database\Seeders\DompetDatabaseSeeder;
use Modules\KoFood\Database\Seeders\KoFoodDatabaseSeeder;
use Modules\Kojek\Database\Seeders\KojekDatabaseSeeder;
use Modules\Payment\Database\Seeders\PaymentDatabaseSeeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CoreDatabaseSeeder::class,
            KoFoodDatabaseSeeder::class,
            KojekDatabaseSeeder::class,
            DompetDatabaseSeeder::class,
            PaymentDatabaseSeeder::class,
        ]);

        $admin = User::where('email', 'admin@komera.local')->first();
        if (! $admin) {
            $admin = User::create([
                'name' => 'Admin Backoffice',
                'email' => 'admin@komera.local',
                'password' => Hash::make('admin12345'),
            ]);
        }

        $superRole = Role::query()->where('name', 'superadmin')->first() ?: Role::create(['name' => 'superadmin', 'guard_name' => 'web']);
        $adminRole = Role::query()->where('name', 'admin')->first() ?: Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::query()->where('name', 'user')->first() ?: Role::create(['name' => 'user', 'guard_name' => 'web']);
        $anggotaRole = Role::query()->where('name', 'anggota')->first() ?: Role::create(['name' => 'anggota', 'guard_name' => 'web']);

        if (! $admin->hasRole('superadmin')) {
            $admin->assignRole($superRole);
        }

        $koperasi = DB::table('koperasi')->select('id')->orderBy('id')->first();
        $koperasiId = $koperasi->id ?? null;

        $superadmin = User::query()->where('email', 'kankerry@gmail.com')->first();
        if (! $superadmin) {
            $superadmin = User::create([
                'name' => 'Kerry Superadmin',
                'email' => 'kankerry@gmail.com',
                'password' => Hash::make('visitek19'),
                'koperasi_id' => null,
            ]);
        } else {
            $superadmin->password = Hash::make('visitek19');
            $superadmin->koperasi_id = null;
            $superadmin->save();
        }
        if (! $superadmin->hasRole('superadmin')) {
            $superadmin->syncRoles([$superRole]);
        }

        $adminUser = User::query()->where('email', 'erry.delphiero@gmail.com')->first();
        if (! $adminUser) {
            $adminUser = User::create([
                'name' => 'Erry Admin',
                'email' => 'erry.delphiero@gmail.com',
                'password' => Hash::make('visitek19'),
                'koperasi_id' => $koperasiId,
            ]);
        } else {
            $adminUser->password = Hash::make('visitek19');
            if ($koperasiId) {
                $adminUser->koperasi_id = $koperasiId;
            }
            $adminUser->save();
        }
        if (! $adminUser->hasRole('admin')) {
            $adminUser->syncRoles([$adminRole]);
        }
    }
}
