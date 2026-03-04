<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TarifDeliveryTokoController extends BaseController
{
    public function index()
    {
        $query = DB::table('delivery_toko_tarif')
            ->leftJoin('merchant', 'delivery_toko_tarif.merchant_id', '=', 'merchant.id')
            ->join('koperasi', 'delivery_toko_tarif.koperasi_id', '=', 'koperasi.id')
            ->select('delivery_toko_tarif.*', 'koperasi.nama_koperasi', 'merchant.nama_toko')
            ->orderBy('start_km');
        $user = Auth::user();
        if ($user && ! $user->hasRole('superadmin')) {
            $query->where('delivery_toko_tarif.koperasi_id', $user->koperasi_id);
        }
        $items = $query->get();

        return view('tarif_delivery.index', compact('items'));
    }

    public function create()
    {
        $user = Auth::user();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        $merchQuery = DB::table('merchant')->select('id', 'nama_toko')->orderBy('nama_toko');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
            $merchQuery->where('koperasi_id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();
        $merchants = $merchQuery->get();

        return view('tarif_delivery.create', compact('koperasis', 'merchants'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'koperasi_id' => ['required', 'exists:koperasi,id'],
            'merchant_id' => ['nullable', 'exists:merchant,id'],
            'start_km' => ['required', 'numeric', 'min:0'],
            'end_km' => ['required', 'numeric', 'gt:start_km'],
            'biaya_dasar' => ['required', 'numeric', 'min:0'],
            'biaya_per_km' => ['required', 'numeric', 'min:0'],
            'min_fare' => ['required', 'numeric', 'min:0'],
            'aktif' => ['nullable', 'boolean'],
        ]);
        $user = Auth::user();
        $data['koperasi_id'] = ($user && $user->hasRole('superadmin')) ? (int) $data['koperasi_id'] : (int) ($user->koperasi_id ?? $data['koperasi_id']);
        $data['aktif'] = $request->boolean('aktif');
        $data['created_at'] = now();
        $data['updated_at'] = now();
        DB::table('delivery_toko_tarif')->insert($data);

        return redirect()->route('tarif-delivery-toko.index')->with('status', 'Tarif delivery dibuat');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $itemQuery = DB::table('delivery_toko_tarif')->where('id', $id);
        if ($user && ! $user->hasRole('superadmin')) {
            $itemQuery->where('koperasi_id', $user->koperasi_id);
        }
        $item = $itemQuery->first();
        abort_if(! $item, 404);
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        $merchQuery = DB::table('merchant')->select('id', 'nama_toko')->orderBy('nama_toko');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
            $merchQuery->where('koperasi_id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();
        $merchants = $merchQuery->get();

        return view('tarif_delivery.edit', compact('item', 'koperasis', 'merchants'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'koperasi_id' => ['required', 'exists:koperasi,id'],
            'merchant_id' => ['nullable', 'exists:merchant,id'],
            'start_km' => ['required', 'numeric', 'min:0'],
            'end_km' => ['required', 'numeric', 'gt:start_km'],
            'biaya_dasar' => ['required', 'numeric', 'min:0'],
            'biaya_per_km' => ['required', 'numeric', 'min:0'],
            'min_fare' => ['required', 'numeric', 'min:0'],
            'aktif' => ['nullable', 'boolean'],
        ]);
        $user = Auth::user();
        $data['koperasi_id'] = ($user && $user->hasRole('superadmin')) ? (int) $data['koperasi_id'] : (int) ($user->koperasi_id ?? $data['koperasi_id']);
        $data['aktif'] = $request->boolean('aktif');
        $data['updated_at'] = now();
        $updateQuery = DB::table('delivery_toko_tarif')->where('id', $id);
        if ($user && ! $user->hasRole('superadmin')) {
            $updateQuery->where('koperasi_id', $user->koperasi_id);
        }
        $updateQuery->update($data);

        return redirect()->route('tarif-delivery-toko.index')->with('status', 'Tarif delivery diperbarui');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $delQuery = DB::table('delivery_toko_tarif')->where('id', $id);
        if ($user && ! $user->hasRole('superadmin')) {
            $delQuery->where('koperasi_id', $user->koperasi_id);
        }
        $delQuery->delete();

        return redirect()->route('tarif-delivery-toko.index')->with('status', 'Tarif delivery dihapus');
    }

    public function show($id)
    {
        return redirect()->route('tarif-delivery-toko.edit', $id);
    }
}
