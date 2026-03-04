<?php

namespace Tests\Feature;

use App\Models\Merchant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SellerProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--force' => true])->run();
        DB::table('koperasi')->insert([
            'id' => 1,
            'kode_koperasi' => 'KOP001',
            'nama_koperasi' => 'Koperasi Test',
            'logo' => null,
            'alamat' => 'Jl. Test',
            'provinsi' => 'Jawa Barat',
            'kab_kota' => 'Bandung',
            'kecamatan' => 'Coblong',
            'desa' => 'Dago',
            'latitude' => -6.9,
            'longitude' => 107.6,
            'status_aktif' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_create_product_and_upload_photo_as_merchant(): void
    {
        Storage::fake('public');

        $merchant = new Merchant();
        $merchant->koperasi_id = 1;
        $merchant->nama_toko = 'Toko Test';
        $merchant->alamat = 'Alamat';
        $merchant->kota = 'Bandung';
        $merchant->provinsi = 'Jawa Barat';
        $merchant->latitude = -6.9;
        $merchant->longitude = 107.6;
        $merchant->status = 'aktif';
        $merchant->save();

        $token = $merchant->createToken('test')->plainTextToken;

        $createRes = $this->withHeaders([
                'X-Koperasi-Id' => '1',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ])
            ->postJson('/api/v1/seller/products', [
                'nama_produk' => 'Produk A',
                'deskripsi' => 'Deskripsi',
                'harga' => '25000',
            ]);
        $createRes->assertCreated();
        $id = (string) ($createRes->json('id') ?? '');
        $this->assertNotEmpty($id);

        $samplePath = realpath(base_path('../mobile/komera_mobile/web/favicon.png'));
        $this->assertNotFalse($samplePath, 'Sample image not found for test');
        $file = new UploadedFile($samplePath, 'foto.png', 'image/png', null, true);
        $uploadRes = $this->withHeaders([
                'X-Koperasi-Id' => '1',
                'Accept' => 'application/json',
                'Authorization' => 'Bearer '.$token,
            ])->post("/api/v1/seller/products/{$id}/photos", ['file' => $file]);
        $uploadRes->assertOk();
        $uploadRes->assertJsonStructure(['url', 'urutan']);
    }
}
