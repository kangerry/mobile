<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TarifKojekController extends BaseController
{
    public function index()
    {
        $query = DB::table('kojek_tarif')
            ->join('koperasi', 'kojek_tarif.koperasi_id', '=', 'koperasi.id')
            ->select('kojek_tarif.*', 'koperasi.nama_koperasi')
            ->orderBy('start_km');
        $user = Auth::user();
        if ($user && ! $user->hasRole('superadmin')) {
            $query->where('kojek_tarif.koperasi_id', $user->koperasi_id);
        }
        $items = $query->get();

        return view('tarif_kojek.index', compact('items'));
    }

    public function create()
    {
        $user = Auth::user();
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();

        return view('tarif_kojek.create', compact('koperasis'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'koperasi_id' => ['required', 'exists:koperasi,id'],
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
        DB::table('kojek_tarif')->insert($data);

        return redirect()->route('tarif-kojek.index')->with('status', 'Tarif berhasil dibuat');
    }

    public function edit($id)
    {
        $user = Auth::user();
        $itemQuery = DB::table('kojek_tarif')->where('id', $id);
        if ($user && ! $user->hasRole('superadmin')) {
            $itemQuery->where('koperasi_id', $user->koperasi_id);
        }
        $item = $itemQuery->first();
        abort_if(! $item, 404);
        $kopQuery = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi');
        if ($user && ! $user->hasRole('superadmin')) {
            $kopQuery->where('id', $user->koperasi_id);
        }
        $koperasis = $kopQuery->get();

        return view('tarif_kojek.edit', compact('item', 'koperasis'));
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'koperasi_id' => ['required', 'exists:koperasi,id'],
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
        $updateQuery = DB::table('kojek_tarif')->where('id', $id);
        if ($user && ! $user->hasRole('superadmin')) {
            $updateQuery->where('koperasi_id', $user->koperasi_id);
        }
        $updateQuery->update($data);

        return redirect()->route('tarif-kojek.index')->with('status', 'Tarif berhasil diperbarui');
    }

    public function destroy($id)
    {
        $user = Auth::user();
        $delQuery = DB::table('kojek_tarif')->where('id', $id);
        if ($user && ! $user->hasRole('superadmin')) {
            $delQuery->where('koperasi_id', $user->koperasi_id);
        }
        $delQuery->delete();

        return redirect()->route('tarif-kojek.index')->with('status', 'Tarif dihapus');
    }

    public function show($id)
    {
        return redirect()->route('tarif-kojek.edit', $id);
    }
}
