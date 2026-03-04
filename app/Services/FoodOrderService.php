<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class FoodOrderService
{
    public function createOrder(array $data): array
    {
        return $data;
    }

    public function getOrder(string $id): array
    {
        return ['id' => $id];
    }

    public function calculateDeliveryFee(float $km, int $koperasiId, ?int $merchantId = null): float
    {
        $query = DB::table('delivery_toko_tarif')
            ->where('koperasi_id', $koperasiId)
            ->where('aktif', true)
            ->where('start_km', '<=', $km)
            ->where('end_km', '>=', $km)
            ->orderByRaw('merchant_id is null')
            ->orderByDesc('merchant_id');

        if ($merchantId) {
            $query->where(function ($q) use ($merchantId) {
                $q->whereNull('merchant_id')->orWhere('merchant_id', $merchantId);
            });
        } else {
            $query->whereNull('merchant_id');
        }

        $tarif = $query->first();
        if ($tarif) {
            $fee = (float) $tarif->biaya_dasar + (float) $tarif->biaya_per_km * $km;

            return max($fee, (float) $tarif->min_fare);
        }

        if ($merchantId) {
            $m = DB::table('merchant')->where('id', $merchantId)->first();
            if ($m && isset($m->biaya_delivery_toko)) {
                return (float) $m->biaya_delivery_toko;
            }
        }

        return 0.0;
    }
}
