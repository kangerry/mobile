<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Merchant extends Model
{
    use HasApiTokens;

    protected $table = 'merchant';

    public $timestamps = true;

    protected $fillable = [
        'koperasi_id',
        'anggota_id',
        'nama_toko',
        'deskripsi',
        'nama_pemilik',
        'email',
        'telepon',
        'nib',
        'pirt',
        'password',
        'alamat',
        'kota',
        'provinsi',
        'latitude',
        'longitude',
        'jam_buka',
        'jam_tutup',
        'aktif_delivery_toko',
        'biaya_delivery_toko',
        'aktif_delivery_kojek',
        'radius_layanan_km',
        'status',
    ];

    protected $hidden = ['password'];
}
