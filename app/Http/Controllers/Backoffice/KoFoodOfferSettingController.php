<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Artisan;

class KoFoodOfferSettingController extends BaseController
{
    public function index()
    {
        $expire = (int) env('KOFOOD_DRIVER_OFFER_EXPIRE_MINUTES', 3);
        $rounds = (int) env('KOFOOD_DRIVER_OFFER_MAX_ROUNDS', 2);
        $topN = (int) env('KOFOOD_DRIVER_OFFER_TOP_N', 8);
        return view('backoffice.kofood_offer_setting', compact('expire', 'rounds', 'topN'));
    }

    public function update(Request $request)
    {
        $this->authorize('gateway.setup');
        $data = $request->validate([
            'expire_minutes' => ['required', 'integer', 'min:1', 'max:120'],
            'max_rounds' => ['required', 'integer', 'min:1', 'max:10'],
            'top_n' => ['required', 'integer', 'min:1', 'max:50'],
        ]);
        $envPath = base_path('.env');
        $env = file_exists($envPath) ? file_get_contents($envPath) : '';
        $env = $this->putEnv($env, 'KOFOOD_DRIVER_OFFER_EXPIRE_MINUTES', (string) $data['expire_minutes']);
        $env = $this->putEnv($env, 'KOFOOD_DRIVER_OFFER_MAX_ROUNDS', (string) $data['max_rounds']);
        $env = $this->putEnv($env, 'KOFOOD_DRIVER_OFFER_TOP_N', (string) $data['top_n']);
        file_put_contents($envPath, $env);
        try { Artisan::call('config:clear'); } catch (\Throwable $e) {}
        return redirect()->route('kofood-offer.setting')->with('success', 'Pengaturan berhasil disimpan');
    }

    private function putEnv(string $env, string $key, string $value): string
    {
        $pattern = "/^{$key}=.*$/m";
        if (preg_match($pattern, $env)) {
            return preg_replace($pattern, "{$key}={$value}", $env);
        }
        $suffix = PHP_EOL."{$key}={$value}".PHP_EOL;
        return $env.$suffix;
    }
}

