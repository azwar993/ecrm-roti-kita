<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Autentikasi') · RotiKita</title>
    <style>
        :root{--cream:#f8f3ea;--paper:#fffdf9;--espresso:#35251f;--muted:#87786f;--terracotta:#c96d4c;--terracotta-dark:#a85135;--line:#e9ded2;--green:#2e7657;--shadow:0 24px 70px rgba(65,43,31,.12}
        *{box-sizing:border-box}body{margin:0;background:radial-gradient(circle at 10% 15%,#f0dfc9 0,transparent 28%),radial-gradient(circle at 90% 85%,#ecd0bf 0,transparent 25%),var(--cream);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif;color:var(--espresso);min-height:100vh;display:grid;place-items:center;padding:28px}
        .auth-shell{display:grid;grid-template-columns:1fr .92fr;max-width:1040px;width:100%;min-height:620px;border:1px solid rgba(93,64,47,.12);border-radius:28px;overflow:hidden;background:var(--paper);box-shadow:var(--shadow)}.brand-panel{padding:48px;background:linear-gradient(145deg,#f4e5d2,#ead0b8);display:flex;flex-direction:column;justify-content:space-between;position:relative;overflow:hidden}.brand-panel:after{content:"";position:absolute;width:300px;height:300px;border:1px solid rgba(111,68,45,.14);border-radius:50%;right:-100px;bottom:-65px;box-shadow:0 0 0 35px rgba(111,68,45,.035),0 0 0 70px rgba(111,68,45,.025)}.brand{display:flex;align-items:center;gap:12px;font-weight:850;letter-spacing:-.04em;font-size:21px}.brand-mark{width:42px;height:42px;border-radius:14px;background:var(--terracotta);color:white;display:grid;place-items:center;font-size:22px}.eyebrow{text-transform:uppercase;font-size:11px;font-weight:800;letter-spacing:.18em;color:#9b5d42}.brand-copy{position:relative;z-index:1;max-width:390px}.brand-copy h1{font-size:clamp(36px,4vw,54px);line-height:1.03;letter-spacing:-.055em;margin:15px 0}.brand-copy p{color:#765d4d;line-height:1.8;font-size:15px;max-width:350px}.bread-art{width:100%;max-width:300px;align-self:center;position:relative;z-index:1;margin:12px 0}.brand-footer{color:#806a59;font-size:12px;position:relative;z-index:1}.form-panel{padding:50px clamp(26px,5vw,62px);display:flex;flex-direction:column;justify-content:center}.form-panel h2{font-size:30px;letter-spacing:-.045em;margin:0 0 8px}.subtitle{color:var(--muted);font-size:14px;line-height:1.6;margin:0 0 28px}.field{margin-bottom:17px}.field label{display:block;font-weight:700;font-size:12px;margin-bottom:8px}.field input{width:100%;border:1px solid var(--line);border-radius:12px;padding:13px 14px;font:inherit;color:var(--espresso);background:#fffefa;outline:none;transition:.2s}.field input:focus{border-color:var(--terracotta);box-shadow:0 0 0 3px rgba(201,109,76,.12)}.field input::placeholder{color:#b0a197}.row-between{display:flex;justify-content:space-between;gap:10px;align-items:center;font-size:12px;color:var(--muted);margin:2px 0 20px}.check{display:flex;align-items:center;gap:8px}.check input{accent-color:var(--terracotta)}.btn{display:inline-flex;justify-content:center;align-items:center;gap:9px;width:100%;border:0;border-radius:12px;padding:14px;background:var(--terracotta);color:white;font-weight:800;font:inherit;cursor:pointer;transition:.2s}.btn:hover{background:var(--terracotta-dark);transform:translateY(-1px)}a{color:var(--terracotta-dark);font-weight:700;text-decoration:none}a:hover{text-decoration:underline}.form-foot{text-align:center;font-size:13px;color:var(--muted);margin-top:22px}.alert{border-radius:12px;padding:12px 14px;margin-bottom:18px;font-size:13px;line-height:1.6}.alert-error{background:#fff0ed;border:1px solid #f5c9be;color:#913f2b}.alert-success{background:#edf8f0;border:1px solid #c9e6d0;color:#226546}.error-list{padding-left:19px;margin:5px 0 0}.small-note{font-size:11px;line-height:1.7;color:var(--muted);margin-top:14px}.mobile-brand{display:none}
        @media(max-width:760px){body{padding:14px}.auth-shell{grid-template-columns:1fr;max-width:500px;min-height:0;border-radius:22px}.brand-panel{display:none}.form-panel{padding:35px 25px}.mobile-brand{display:flex;align-items:center;gap:10px;margin-bottom:28px;font-weight:850;font-size:18px}.form-panel h2{font-size:27px}}
    </style>
</head>
<body>
<div class="auth-shell">
    <section class="brand-panel">
        <div class="brand"><span class="brand-mark">R</span><span>RotiKita<span style="color:var(--terracotta)">.</span></span></div>
        <div class="brand-copy">
            <div class="eyebrow">Bakery · Customer experience</div>
            <h1>Roti hangat.<br>Hubungan yang dekat.</h1>
            <p>Tempat yang lebih sederhana untuk menikmati produk favorit, mengikuti pesanan, dan tetap terhubung dengan RotiKita.</p>
            <svg class="bread-art" viewBox="0 0 320 190" role="img" aria-label="Ilustrasi roti dan gandum">
                <ellipse cx="160" cy="160" rx="118" ry="15" fill="#9f6849" opacity=".13"/>
                <path d="M69 119C43 98 54 60 87 51C107 25 146 39 160 48C187 26 223 39 235 61C269 72 274 106 250 127C224 151 94 151 69 119Z" fill="#c77b45" stroke="#98613b" stroke-width="3"/>
                <path d="M91 96C102 81 110 72 125 64M131 111C141 93 150 78 164 65M174 112C185 92 198 78 211 69" stroke="#f8dba8" stroke-width="8" stroke-linecap="round" fill="none"/>
                <path d="M37 142C48 111 44 87 32 70M32 91C16 87 13 77 13 67C27 66 36 76 32 91ZM39 112C56 106 61 95 58 84C43 86 35 98 39 112Z" stroke="#68805b" stroke-width="4" fill="#8ca277" stroke-linejoin="round"/>
                <path d="M282 143C270 114 275 90 286 73M286 94C301 88 307 78 305 68C291 70 282 80 286 94ZM279 114C263 108 257 97 260 87C274 89 283 101 279 114Z" stroke="#68805b" stroke-width="4" fill="#8ca277" stroke-linejoin="round"/>
                <circle cx="250" cy="37" r="4" fill="#c96d4c"/><circle cx="64" cy="39" r="3" fill="#c96d4c"/><path d="M251 48l5 7 7 1-5 5 1 7-8-4-6 4 1-7-5-5 7-1z" fill="#b9874b"/>
            </svg>
        </div>
        <div class="brand-footer">© {{ date('Y') }} RotiKita · Customer Relationship Management</div>
    </section>
    <section class="form-panel">
        <div class="mobile-brand"><span class="brand-mark">R</span> RotiKita<span style="color:var(--terracotta)">.</span></div>
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-error">
                <strong>Ada yang perlu diperbaiki.</strong>
                <ul class="error-list">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif
        @yield('content')
    </section>
</div>
</body>
</html>
