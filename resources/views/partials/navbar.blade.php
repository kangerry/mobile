<header class="header">
    <div style="display:flex; align-items:center; gap:8px;">
        <button class="btn-brand" data-sidebar-toggle><i class="fa-solid fa-bars"></i></button>
        <div style="font-weight:600; color:#fff;">{{ $title ?? 'Backoffice' }}</div>
    </div>
    <div style="display:flex; align-items:center; gap:12px;">
        @php
            $user = auth()->user();
            $activeKopId = session('active_koperasi_id');
            $activeKopNama = session('active_koperasi_nama');
        @endphp
        @if($user && $user->hasRole('superadmin'))
            <form method="POST" action="{{ route('active-koperasi.set') }}" style="display:flex;align-items:center;gap:6px;">
                @csrf
                <label style="color:#fff;">Koperasi Aktif</label>
                <select name="koperasi_id" onchange="this.form.submit()" style="padding:6px 8px;border-radius:6px;border:1px solid rgba(255,255,255,.3);background:#fff;">
                    <option value="">— Pilih —</option>
                    @foreach(\Illuminate\Support\Facades\DB::table('koperasi')->select('id','nama_koperasi')->orderBy('nama_koperasi')->get() as $k)
                        <option value="{{ $k->id }}" @if((int)($activeKopId ?? 0) === (int)$k->id) selected @endif>{{ $k->nama_koperasi }}</option>
                    @endforeach
                </select>
            </form>
        @else
            @php
                $kopNama = null;
                if ($user && $user->koperasi_id) {
                    $kopNama = \Illuminate\Support\Facades\DB::table('koperasi')->where('id',$user->koperasi_id)->value('nama_koperasi');
                }
            @endphp
            @if($kopNama)
                <span class="badge" style="background:#1f6feb;color:#fff;border-radius:6px;padding:6px 10px;">{{ $kopNama }}</span>
            @endif
        @endif
        <span style="color:#fff;">{{ $user->name ?? 'Admin' }}</span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button class="btn-brand" type="submit"><i class="fa-solid fa-right-from-br"></i> Logout</button>
        </form>
    </div>
</header>
