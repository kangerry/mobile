<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class KoperasiController extends BaseController
{
    public function index()
    {
        return view('koperasi.index');
    }

    public function create()
    {
        return view('koperasi.create');
    }

    public function store(Request $request)
    {
        return redirect()->route('koperasi.index')->with('status', 'Data tersimpan (dummy)');
    }

    public function edit($id)
    {
        return view('koperasi.edit', compact('id'));
    }

    public function update(Request $request, $id)
    {
        return redirect()->route('koperasi.index')->with('status', 'Data diperbarui (dummy)');
    }

    public function destroy($id)
    {
        return redirect()->route('koperasi.index')->with('status', 'Data dihapus (dummy)');
    }

    public function show($id)
    {
        return redirect()->route('koperasi.edit', $id);
    }
}
