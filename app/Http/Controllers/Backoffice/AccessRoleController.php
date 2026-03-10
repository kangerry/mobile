<?php

namespace App\Http\Controllers\Backoffice;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class AccessRoleController extends BaseController
{
    public function index()
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();
        $counts = DB::table('model_has_roles')
            ->select('role_id', DB::raw('count(*) as c'))
            ->where('model_type', \App\Models\User::class)
            ->groupBy('role_id')
            ->pluck('c', 'role_id');

        return view('access_roles.index', compact('roles', 'counts'));
    }

    public function create()
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }

        return view('access_roles.create');
    }

    public function store(Request $request)
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $v = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:roles,name'],
        ]);
        Role::create(['name' => $v['name'], 'guard_name' => 'web']);

        return redirect()->route('access-roles.index')->with('status', 'Role dibuat');
    }

    public function edit($id)
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $role = Role::find((int) $id);
        if (! $role) {
            return redirect()->route('access-roles.index')->with('status', 'Data tidak ditemukan');
        }
        $perms = Permission::query()->where('guard_name', 'web')->orderBy('name')->get();
        $selected = $role->permissions->pluck('name')->all();

        return view('access_roles.edit', compact('role', 'perms', 'selected'));
    }

    public function update(Request $request, $id)
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $role = Role::find((int) $id);
        if (! $role) {
            return redirect()->route('access-roles.index')->with('status', 'Data tidak ditemukan');
        }
        $v = $request->validate([
            'name' => ['required', 'string', 'max:150', 'unique:roles,name,'.$role->id],
            'permissions' => ['array'],
            'permissions.*' => ['string'],
        ]);
        $role->name = $v['name'];
        $role->save();
        $permNames = collect($v['permissions'] ?? [])->values()->all();
        $permModels = Permission::query()->where('guard_name', 'web')->whereIn('name', $permNames)->get();
        $role->syncPermissions($permModels);

        return redirect()->route('access-roles.edit', $role->id)->with('status', 'Perubahan disimpan');
    }

    public function destroy($id)
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $role = Role::find((int) $id);
        if (! $role) {
            return redirect()->route('access-roles.index')->with('status', 'Data tidak ditemukan');
        }
        if ($role->name === 'superadmin') {
            return redirect()->route('access-roles.index')->with('status', 'Tidak dapat menghapus role ini');
        }
        $role->delete();

        return redirect()->route('access-roles.index')->with('status', 'Role dihapus');
    }
}

