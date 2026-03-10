<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;

class AccessPermissionController extends BaseController
{
    public function index()
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $perms = Permission::query()->where('guard_name', 'web')->orderBy('name')->get();

        return view('permissions.index', compact('perms'));
    }

    public function store(Request $request)
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $v = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:permissions,name'],
        ]);
        Permission::create(['name' => $v['name'], 'guard_name' => 'web']);

        return redirect()->route('permissions.index')->with('status', 'Permission ditambahkan');
    }

    public function destroy($id)
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $perm = Permission::find((int) $id);
        if ($perm) {
            if ($perm->name === 'access.all') {
                return redirect()->route('permissions.index')->with('status', 'Tidak dapat menghapus permission ini');
            }
            $perm->delete();
        }
        return redirect()->route('permissions.index')->with('status', 'Permission dihapus');
    }
}

