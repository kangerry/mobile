<?php

namespace App\Http\Controllers\Backoffice;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserController extends BaseController
{
    public function index()
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $items = DB::table('users')
            ->leftJoin('model_has_roles', function ($join) {
                $join->on('users.id', '=', 'model_has_roles.model_id')
                    ->where('model_has_roles.model_type', User::class);
            })
            ->leftJoin('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->leftJoin('koperasi', 'users.koperasi_id', '=', 'koperasi.id')
            ->select('users.id', 'users.name', 'users.email', 'users.koperasi_id', 'koperasi.nama_koperasi', 'roles.name as role_name')
            ->orderByDesc('users.id')
            ->get();

        return view('user.index', compact('items'));
    }

    public function create()
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $koperasis = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi')->get();

        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();

        return view('user.create', compact('koperasis', 'roles'));
    }

    public function store(Request $request)
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $v = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'koperasi_id' => ['nullable', 'integer'],
        ]);
        $roleName = $v['role'];
        $roleModel = Role::where('guard_name', 'web')->where('name', $roleName)->first();
        $hasAccessAll = ($roleName === 'superadmin');
        if ($roleModel && ! $hasAccessAll) {
            $hasAccessAll = $roleModel->permissions()->where('name', 'access.all')->exists();
        }
        $kopId = $hasAccessAll ? null : (int) ($v['koperasi_id'] ?? 0);
        if (! $hasAccessAll && ! $kopId) {
            return back()->with('status', 'Pilih koperasi untuk admin')->withInput();
        }
        if ($kopId) {
            $exists = DB::table('koperasi')->where('id', $kopId)->exists();
            if (! $exists) {
                return back()->with('status', 'Koperasi tidak ditemukan')->withInput();
            }
        }
        $u = new User();
        $u->name = $v['name'];
        $u->email = strtolower(trim($v['email']));
        $u->password = Hash::make($v['password']);
        $u->koperasi_id = $kopId;
        $u->save();
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $u->syncRoles([$role]);

        return redirect()->route('users.index')->with('status', 'User dibuat');
    }

    public function edit($id)
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $row = DB::table('users')->where('id', (int) $id)->first();
        if (! $row) {
            return redirect()->route('users.index')->with('status', 'Data tidak ditemukan');
        }
        $roleRow = DB::table('model_has_roles')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('model_has_roles.model_type', User::class)
            ->where('model_has_roles.model_id', (int) $id)
            ->select('roles.name')
            ->first();
        $role = $roleRow->name ?? null;
        $koperasis = DB::table('koperasi')->select('id', 'nama_koperasi')->orderBy('nama_koperasi')->get();
        $roles = Role::query()->where('guard_name', 'web')->orderBy('name')->get();

        return view('user.edit', ['row' => $row, 'role' => $role, 'koperasis' => $koperasis, 'roles' => $roles]);
    }

    public function update(Request $request, $id)
    {
        $auth = Auth::user();
        if (! $auth || (! $auth->hasRole('superadmin') && ! $auth->can('users.manage'))) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $v = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email,'.(int) $id],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', 'string', 'exists:roles,name'],
            'koperasi_id' => ['nullable', 'integer'],
        ]);
        $u = User::find((int) $id);
        if (! $u) {
            return redirect()->route('users.index')->with('status', 'Data tidak ditemukan');
        }
        $roleName = $v['role'];
        $roleModel = Role::where('guard_name', 'web')->where('name', $roleName)->first();
        $hasAccessAll = ($roleName === 'superadmin');
        if ($roleModel && ! $hasAccessAll) {
            $hasAccessAll = $roleModel->permissions()->where('name', 'access.all')->exists();
        }
        $kopId = $hasAccessAll ? null : (int) ($v['koperasi_id'] ?? 0);
        if (! $hasAccessAll && ! $kopId) {
            return back()->with('status', 'Pilih koperasi untuk admin')->withInput();
        }
        if ($kopId) {
            $exists = DB::table('koperasi')->where('id', $kopId)->exists();
            if (! $exists) {
                return back()->with('status', 'Koperasi tidak ditemukan')->withInput();
            }
        }
        $u->name = $v['name'];
        $u->email = strtolower(trim($v['email']));
        if (! empty($v['password'])) {
            $u->password = Hash::make($v['password']);
        }
        $u->koperasi_id = $kopId;
        $u->save();
        $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
        $u->syncRoles([$role]);

        return redirect()->route('users.index')->with('status', 'Perubahan disimpan');
    }

    public function destroy($id)
    {
        $auth = Auth::user();
        if (! $auth || ! $auth->hasRole('superadmin')) {
            return redirect()->route('dashboard')->with('status', 'Tidak diizinkan');
        }
        $targetId = (int) $id;
        if ($targetId === $auth->id) {
            return redirect()->route('users.index')->with('status', 'Tidak dapat menghapus diri sendiri');
        }
        DB::table('users')->where('id', $targetId)->delete();
        DB::table('model_has_roles')->where('model_type', User::class)->where('model_id', $targetId)->delete();

        return redirect()->route('users.index')->with('status', 'User dihapus');
    }
}

