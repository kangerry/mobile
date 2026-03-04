<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\DB;

class GatewayLogController extends BaseController
{
    public function index(Request $request)
    {
        $q = DB::table('transaksi_gateway')
            ->join('koperasi', 'transaksi_gateway.koperasi_id', '=', 'koperasi.id')
            ->select('transaksi_gateway.*', 'koperasi.nama_koperasi')
            ->where('tipe_transaksi', 'TOPUP_DOMPET');
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }
        if ($request->filled('kop')) {
            $q->where('koperasi_id', (int) $request->query('kop'));
        }
        if ($request->filled('from')) {
            $from = date('Y-m-d 00:00:00', strtotime($request->query('from')));
            $q->where('transaksi_gateway.updated_at', '>=', $from);
        }
        if ($request->filled('to')) {
            $to = date('Y-m-d 23:59:59', strtotime($request->query('to')));
            $q->where('transaksi_gateway.updated_at', '<=', $to);
        }
        if ($request->filled('q')) {
            $search = '%'.str_replace(' ', '%', $request->query('q')).'%';
            $q->where(function ($sub) use ($search) {
                $sub->where('nomor_invoice', 'like', $search)
                    ->orWhere('external_id', 'like', $search);
            });
        }
        if ($request->filled('min_amount')) {
            $q->where('transaksi_gateway.jumlah', '>=', (float) $request->query('min_amount'));
        }
        if ($request->filled('max_amount')) {
            $q->where('transaksi_gateway.jumlah', '<=', (float) $request->query('max_amount'));
        }
        $perPage = (int) $request->query('per_page', 50);
        $items = $q->orderByDesc('id')->paginate($perPage)->withQueryString();

        return view('gateway_logs.index', compact('items'));
    }

    public function export(Request $request)
    {
        $q = DB::table('transaksi_gateway')
            ->join('koperasi', 'transaksi_gateway.koperasi_id', '=', 'koperasi.id')
            ->select('transaksi_gateway.*', 'koperasi.nama_koperasi')
            ->where('tipe_transaksi', 'TOPUP_DOMPET');
        if ($request->filled('status')) {
            $q->where('status', $request->query('status'));
        }
        if ($request->filled('kop')) {
            $q->where('koperasi_id', (int) $request->query('kop'));
        }
        if ($request->filled('from')) {
            $from = date('Y-m-d 00:00:00', strtotime($request->query('from')));
            $q->where('transaksi_gateway.updated_at', '>=', $from);
        }
        if ($request->filled('to')) {
            $to = date('Y-m-d 23:59:59', strtotime($request->query('to')));
            $q->where('transaksi_gateway.updated_at', '<=', $to);
        }
        if ($request->filled('q')) {
            $search = '%'.str_replace(' ', '%', $request->query('q')).'%';
            $q->where(function ($sub) use ($search) {
                $sub->where('nomor_invoice', 'like', $search)
                    ->orWhere('external_id', 'like', $search);
            });
        }
        if ($request->filled('min_amount')) {
            $q->where('transaksi_gateway.jumlah', '>=', (float) $request->query('min_amount'));
        }
        if ($request->filled('max_amount')) {
            $q->where('transaksi_gateway.jumlah', '<=', (float) $request->query('max_amount'));
        }
        $filename = 'gateway_logs_'.date('Ymd_His').'.csv';

        return response()->streamDownload(function () use ($q) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['ID', 'Koperasi', 'Invoice', 'VA', 'Jumlah', 'Status', 'Updated At']);
            foreach ($q->orderByDesc('id')->cursor() as $r) {
                fputcsv($out, [
                    $r->id,
                    $r->nama_koperasi,
                    $r->nomor_invoice,
                    $r->external_id,
                    (float) $r->jumlah,
                    $r->status,
                    $r->updated_at,
                ]);
            }
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
