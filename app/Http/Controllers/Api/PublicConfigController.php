<?php

namespace App\Http\Controllers\Api;

use Illuminate\Routing\Controller as BaseController;

class PublicConfigController extends BaseController
{
    public function show()
    {
        return response()->json([
            'maps_api_key' => (string) env('MAPS_API_KEY', ''),
            'onesignal_app_id' => (string) env('ONESIGNAL_APP_ID', ''),
        ])->header('Access-Control-Allow-Origin', '*')
          ->header('Access-Control-Allow-Methods', 'GET, OPTIONS')
          ->header('Access-Control-Allow-Headers', 'Content-Type, X-Requested-With');
    }
}
