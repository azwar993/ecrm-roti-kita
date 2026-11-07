<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Dashboard') · RotiKita</title>
    <style>
        :root{--cream:#f7f3ec;--paper:#fffefa;--espresso:#35251f;--muted:#85766c;--terracotta:#c96d4c;--line:#e9e0d6;--green:#2e7657;--shadow:0 12px 40px rgba(65,43,31,.06)}
        *{box-sizing:border-box}body{margin:0;background:var(--cream);color:var(--espresso);font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif}.app{min-height:100vh;display:grid;grid-template-columns:250px 1fr}.sidebar{background:#35251f;color:#f9f1e6;padding:25px 17px;display:flex;flex-direction:column}.brand{display:flex;align-items:center;gap:10px;font-weight:850;font-size:20px;letter-spacing:-.04em;padding:4px 10px 28px}.brand-mark{width:38px;height:38px;display:grid;place-items:center;border-radius:12px;background:var(--terracotta);color:#fff}.nav-label{color:#bca99a;text-transform:uppercase;font-size:10px;font-weight:800;letter-spacing:.16em;padding:10px 12px}.nav-link{display:flex;align-items:center;gap:12px;padding:12px;border-radius:11px;color:#e9dbcf;text-decoration:none;font-size:13px;margin:2px 0}.nav-link.active,.nav-link:hover{background:#4c382e;color:#fff;text-decoration:none}.nav-link .symbol{width:20px;text-align:center;color:#e4ad8e}.side-bottom{margin-top:auto;padding:15px 11px 3px;color:#bca99a;font-size:11px;line-height:1.6}.main{min-width:0}.topbar{height:74px;background:rgba(255,254,250,.88);border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between;padding:0 34px;gap:20px}.crumb{font-size:12px;color:var(--muted)}.user-chip{display:flex;align-items:center;gap:11px;font-size:12px}.avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;background:#f0dfd0;color:#8b5037;font-weight:800}.user-name{font-weight:750}.user-role{font-size:10px;color:var(--muted);margin-top:2px}.logout{border:1px solid var(--line);border-radius:9px;background:#fffefa;color:var(--espresso);font:inherit;font-size:12px;padding:9px 12px;cursor:pointer}.logout:hover{border-color:var(--terracotta);color:var(--terracotta)}.content{padding:32px 34px;max-width:1480px;margin:0 auto}.page-heading{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:25px}.eyebrow{color:var(--terracotta);font-weight:800;font-size:10px;text-transform:uppercase;letter-spacing:.15em}.page-heading h1{font-size:30px;line-height:1.15;letter-spacing:-.045em;margin:8px 0}.page-heading p{color:var(--muted);font-size:13px;margin:0;line-height:1.6}.card{background:var(--paper);border:1px solid var(--line);border-radius:17px;padding:22px;box-shadow:var(--shadow)}.grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:17px}.stat-label{font-size:12px;color:var(--muted)}.stat-value{font-size:29px;font-weight:850;letter-spacing:-.05em;margin:12px 0 6px}.stat-caption{font-size:11px;color:var(--green)}.welcome{background:linear-gradient(115deg,#f1dfcd,#f8eee1);border:1px solid #ead7c3;border-radius:18px;padding:24px 27px;display:flex;align-items:center;justify-content:space-between;gap:24px;margin-bottom:20px}.welcome h2{margin:6px 0 8px;font-size:23px;letter-spacing:-.04em}.welcome p{color:#806957;font-size:13px;line-height:1.7;margin:0;max-width:650px}.welcome-icon{font-size:52px;flex-shrink:0}.quick-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:15px;margin-top:20px}.quick{border:1px solid var(--line);border-radius:14px;padding:18px;background:#fffefa}.quick-icon{width:36px;height:36px;background:#f7e6d8;border-radius:11px;display:grid;place-items:center;font-size:18px;margin-bottom:12px}.quick strong{font-size:13px}.quick p{color:var(--muted);font-size:12px;line-height:1.6;margin:7px 0 0}.alert-success{background:#edf8f0;border:1px solid #c9e6d0;color:#226546;border-radius:12px;padding:12px 14px;margin-bottom:20px;font-size:13px}.muted{color:var(--muted)}@media(max-width:900px){.app{grid-template-columns:75px 1fr}.sidebar{padding:22px 8px}.brand{justify-content:center;padding:4px 0 25px;font-size:0}.brand-mark{font-size:18px}.nav-label{font-size:0;padding:8px}.nav-link{justify-content:center;font-size:0;padding:13px 6px}.nav-link .symbol{font-size:17px}.side-bottom{font-size:0}.content{padding:24px 20px}.topbar{padding:0 20px}.grid,.quick-grid{grid-template-columns:1fr 1fr}}@media(max-width:560px){.grid,.quick-grid{grid-template-columns:1fr}.welcome{align-items:flex-start}.welcome-icon{display:none}.topbar{height:auto;min-height:68px}.page-heading h1{font-size:25px}}
    </style>
</head>
<body>
<div class="app">
    <aside class="sidebar">
        <div class="brand"><span class="brand-mark">R</span><span>RotiKita<span style="color:#e39b79">.</span></span></div>
        <div class="nav-label">Menu utama</div>
        @if (auth()->user()->role === 'admin')
            <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}"><span class="symbol">▦</span>Dashboard</a>
            <a class="nav-link" href="#pelanggan"><span class="symbol">♙</span>Pelanggan <small style="margin-left:auto;color:#bca99a">Segera</small></a>
            <a class="nav-link" href="#produk"><span class="symbol">▤</span>Produk <small style="margin-left:auto;color:#bca99a">Segera</small></a>
            <a class="nav-link" href="#pesanan"><span class="symbol">▣</span>Pemesanan <small style="margin-left:auto;color:#bca99a">Segera</small></a>
            <a class="nav-link" href="#promosi"><span class="symbol">✳</span>Promosi <small style="margin-left:auto;color:#bca99a">Segera</small></a>
            <a class="nav-link" href="#feedback"><span class="symbol">✉</span>Feedback <small style="margin-left:auto;color:#bca99a">Segera</small></a>
        @else
            <a class="nav-link {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}" href="{{ route('customer.dashboard') }}"><span class="symbol">⌂</span>Beranda</a>
            <a class="nav-link" href="#katalog"><span class="symbol">▤</span>Katalog <small style="margin-left:auto;color:#bca99a">Segera</small></a>
            <a class="nav-link" href="#pesanan"><span class="symbol">▣</span>Pesanan <small style="margin-left:auto;color:#bca99a">Segera</small></a>
            <a class="nav-link" href="#feedback"><span class="symbol">✉</span>Feedback <small style="margin-left:auto;color:#bca99a">Segera</small></a>
        @endif
        <div class="side-bottom">RotiKita E-CRM<br>Kelola hubungan, bukan hanya transaksi.</div>
    </aside>
    <main class="main">
        <header class="topbar">
            <div class="crumb">RotiKita <span style="padding:0 7px">/</span> @yield('breadcrumb', 'Dashboard')</div>
            <div class="user-chip">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</div>
                <div><div class="user-name">{{ auth()->user()->name }}</div><div class="user-role">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Customer' }}</div></div>
                <form method="POST" action="{{ route('logout') }}">@csrf<button class="logout" type="submit">Keluar ↗</button></form>
            </div>
        </header>
        <section class="content">
            @if (session('success'))<div class="alert-success">{{ session('success') }}</div>@endif
            @yield('content')
        </section>
    </main>
</div>
</body>
</html>
