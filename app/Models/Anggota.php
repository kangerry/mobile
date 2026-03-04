<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Anggota extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'anggota';

    public $timestamps = true;

    protected $fillable = [
        'koperasi_id',
        'nomor_anggota',
        'nama_anggota',
        'email',
        'telepon',
        'password',
        'login_google_id',
        'status',
    ];

    protected $hidden = ['password'];
}
