<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AuthLoginGoogleTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->run();
        DB::table('koperasi')->delete();
    }

    public function test_login_google_creates_default_koperasi_and_anggota_when_missing(): void
    {
        $res = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Koperasi-Id' => '9999',
        ])->postJson('/api/v1/auth/login-google', [
            'google_id' => 'gid-999',
            'email' => 'tester@example.com',
            'name' => 'Tester',
        ]);
        $res->assertOk();
        $res->assertJsonStructure(['token', 'role', 'user' => ['id']]);

        $uid = (int) $res->json('user.id');
        $this->assertTrue($uid > 0);
        $kopId = (int) $res->json('koperasi_id');
        $this->assertTrue(DB::table('koperasi')->where('id', $kopId)->exists());
        $this->assertTrue(DB::table('anggota')->where('id', $uid)->exists());
    }

    public function test_register_anggota_succeeds_when_koperasi_missing(): void
    {
        $res = $this->withHeaders([
            'Accept' => 'application/json',
            'X-Koperasi-Id' => '12345',
        ])->postJson('/api/v1/auth/register-anggota', [
            'nama' => 'User A',
            'email' => 'usera@example.com',
            'password' => 'secret123',
        ]);
        $res->assertCreated();
        $res->assertJsonStructure(['token', 'role', 'user' => ['id']]);

        $uid = (int) $res->json('user.id');
        $kopId = (int) $res->json('koperasi_id');
        $this->assertTrue(DB::table('koperasi')->where('id', $kopId)->exists());
        $this->assertTrue(DB::table('anggota')->where('id', $uid)->exists());
    }
}

