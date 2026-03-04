<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class RideOrderService
{
    public function requestRide(array $data): array
    {
        return $data;
    }

    public function getRideStatus(string $id): array
    {
        return ['id' => $id, 'status' => 'pending'];
    }

    public function calculateFare(float $km, int $koperasiId): float
    {
        $tarif = DB::table('kojek_tarif')
            ->where('koperasi_id', $koperasiId)
            ->where('aktif', true)
            ->where('start_km', '<=', $km)
            ->where('end_km', '>=', $km)
            ->orderBy('start_km')
            ->first();

        if ($tarif) {
            $fare = (float) $tarif->biaya_dasar + (float) $tarif->biaya_per_km * $km;

            return max($fare, (float) $tarif->min_fare);
        }

        return 0.0;
    }
}
