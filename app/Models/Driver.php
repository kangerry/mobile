<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;

class Driver extends Model
{
    use HasApiTokens;

    protected $table = 'driver';

    protected $fillable = [
        'koperasi_id',
        'nama_driver',
        'email',
        'telepon',
        'password',
        'jenis_kendaraan',
        'plat_nomor',
        'nomor_sim',
        'terverifikasi',
        'status_online',
        'latitude_terakhir',
        'longitude_terakhir',
        'rating',
    ];

    protected $hidden = ['password'];
}
