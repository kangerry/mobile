<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TenantMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->is('api/*')) {
            return $next($request);
        }
        if ($request->getMethod() === 'OPTIONS') {
            return $next($request);
        }

        $koperasiId = $request->header('X-Koperasi-Id') ?? $request->query('koperasi_id');

        if (empty($koperasiId)) {
            return response()->json([
                'message' => 'koperasi_id is required',
            ], 400);
        }

        $request->attributes->set('koperasi_id', (string) $koperasiId);

        return $next($request);
    }
}
