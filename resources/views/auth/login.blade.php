<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login • KOMERA Backoffice</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
    <style>
        :root{--brand:#0d47a1;--brand-dark:#0b3b86;--text:#0f172a;--muted:#64748b}
        *{box-sizing:border-box}
        body{min-height:100vh;margin:0;display:flex;align-items:center;justify-content:center;background:#f5f7fb}
        .shell{width:100%;max-width:1000px;padding:20px}
        .card-hero{display:grid;grid-template-columns:1.1fr 0.9fr;background:#fff;border-radius:18px;box-shadow:0 20px 60px rgba(13,71,161,.20);overflow:hidden}
        .hero-left{position:relative;padding:36px;background:radial-gradient(1200px circle at -10% -10%, #1e429f 5%, #0d47a1 50%, #0b1f4d 100%);color:#fff}
        .hero-shape{position:absolute;border-radius:50%;filter:blur(12px);opacity:.15}
        .shape-1{width:260px;height:260px;left:20px;bottom:20px;background:#ffffff}
        .shape-2{width:180px;height:180px;right:40px;top:40px;background:#60a5fa}
        .welcome{font-weight:800;font-size:28px;margin:0 0 8px}
        .tagline{opacity:.9;line-height:1.6;max-width:460px}
        .brand{display:flex;align-items:center;gap:12px;margin-bottom:18px}
        .brand-logo{width:56px;height:56px;object-fit:contain;border-radius:12px;background:rgba(255,255,255,.1);padding:6px}
        .form-wrap{padding:28px}
        .login-title{font-weight:800;font-size:22px;color:var(--text);margin:0 0 6px}
        .login-sub{color:var(--muted);margin-bottom:16px}
        .form-group{margin-bottom:12px}
        .form-group label{display:block;margin-bottom:6px;color:var(--text);font-weight:600}
        .field{position:relative}
        .field input{width:100%;padding:12px 44px 12px 12px;border:1px solid #e5e7eb;border-radius:10px;transition:border-color .2s}
        .field input:focus{outline:none;border-color:var(--brand);box-shadow:0 0 0 3px rgba(13,71,161,.1)}
        .toggle-eye{position:absolute;right:10px;top:50%;transform:translateY(-50%);border:none;background:transparent;cursor:pointer;color:#475569}
        .actions{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;color:var(--muted);font-size:13px}
        .btn{display:inline-flex;align-items:center;gap:8px;background:var(--brand);color:#fff;border:none;padding:12px;border-radius:10px;width:100%;justify-content:center;font-weight:600}
        .btn:hover{background:var(--brand-dark)}
        .alert{display:flex;align-items:flex-start;gap:10px;background:#ecfeff;border:1px solid #bae6fd;color:#0c4a6e;padding:10px 12px;border-radius:10px;margin-bottom:12px}
        .alert.error{background:#fef2f2;border-color:#fecaca;color:#7f1d1d}
        .foot{margin-top:12px;text-align:center;color:var(--muted);font-size:13px}
        @media (max-width: 860px){.card-hero{grid-template-columns:1fr}.hero-left{padding:24px}.tagline{display:none}}
    </style>
</head>
<body>
    <div class="shell">
        <div class="card-hero">
            <div class="hero-left">
                @php($logoPath = public_path('assets/img/komera-logo.png'))
                <div class="brand">
                    @if (file_exists($logoPath))
                        <img src="{{ asset('assets/img/komera-logo.png') }}" alt="KOMERA" class="brand-logo" onerror="this.style.display='none'">
                    @else
                        <i class="fa-solid fa-cubes" style="font-size:28px;color:#fff"></i>
                    @endif
                    <div class="welcome">WELCOME</div>
                </div>
                <div class="tagline">Masuk ke KOMERA Backoffice untuk mengelola koperasi, merchant, driver, dan transaksi secara terintegrasi.</div>
                <div class="hero-shape shape-1"></div>
                <div class="hero-shape shape-2"></div>
            </div>
            <div class="form-wrap">
                <div class="login-title">Sign in</div>
                <div class="login-sub">Gunakan email dan kata sandi Anda</div>
                @if(session('status'))
                    <div class="alert"><i class="fa-solid fa-circle-info"></i><div>{{ session('status') }}</div></div>
                @endif
                @if($errors->any())
                    <div class="alert error"><i class="fa-solid fa-circle-exclamation"></i><div>{{ $errors->first() }}</div></div>
                @endif
                <form method="POST" action="{{ route('login.submit') }}">
                    @csrf
                    <div class="form-group">
                        <label for="email">Email</label>
                        <div class="field"><input type="email" id="email" name="email" value="{{ old('email') }}" required autofocus></div>
                    </div>
                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="field">
                            <input type="password" id="password" name="password" required>
                            <button type="button" class="toggle-eye" aria-label="toggle password" onclick="(function(el){var i=document.getElementById('password');i.type=i.type==='password'?'text':'password'; el.innerHTML=i.type==='password'?'<i class=&quot;fa-solid fa-eye&quot;></i>':'<i class=&quot;fa-solid fa-eye-slash&quot;></i>'})(this)"><i class="fa-solid fa-eye"></i></button>
                        </div>
                    </div>
                    <div class="actions">
                        <label><input type="checkbox" id="remember" name="remember"> Ingat saya</label>
                        <a href="#" style="color:var(--brand)">Lupa password?</a>
                    </div>
                    <button class="btn" type="submit"><i class="fa-solid fa-right-to-bracket"></i> Sign in</button>
                    <div class="foot">Belum punya akun? Hubungi administrator</div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
