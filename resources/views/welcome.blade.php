<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f8f3ea">
    <title>RotiKita Bakery — Roti Hangat Setiap Hari</title>
    @include('partials.brand-icons')
    <style>
        :root{color-scheme:light;--cream:#f8f3ea;--espresso:#38251e;--terracotta:#b65d3c}*{box-sizing:border-box}body{margin:0;min-height:100vh;display:grid;place-items:center;padding:24px;background:var(--cream);color:var(--espresso);font:16px/1.6 system-ui,sans-serif}.card{max-width:680px;padding:clamp(28px,6vw,56px);border:1px solid #e9ded0;border-radius:28px;background:#fffdf9;box-shadow:0 18px 55px #38251e12}.brand{display:flex;align-items:center;gap:12px;color:inherit;text-decoration:none;font-weight:800;font-size:1.25rem}.mark{display:grid;width:44px;height:44px;place-items:center;border-radius:14px;background:var(--terracotta);color:#fff4e5;font-family:Georgia,serif;font-size:1.6rem}.eyebrow{margin:36px 0 8px;color:var(--terracotta);font-size:.75rem;font-weight:800;letter-spacing:.14em;text-transform:uppercase}h1{margin:0;font-family:Georgia,serif;font-size:clamp(2.3rem,7vw,4rem);line-height:1.05}p{color:#766359}.actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:28px}.button{display:inline-block;padding:12px 18px;border-radius:999px;background:var(--terracotta);color:#fff;text-decoration:none;font-weight:700}.button.secondary{background:#f3e8dc;color:var(--espresso)}
    </style>
</head>
<body>
    <main class="card">
        <a class="brand" href="{{ route('home') }}"><span class="mark" aria-hidden="true">R</span> RotiKita Bakery</a>
        <p class="eyebrow">Roti Hangat Setiap Hari</p>
        <h1>Selamat datang di RotiKita.</h1>
        <p>Nikmati roti, cake, pastry, dan pilihan bakery yang dibuat untuk menemani hari.</p>
        <div class="actions"><a class="button" href="{{ route('home') }}">Kunjungi beranda</a><a class="button secondary" href="{{ route('storefront.menu') }}">Jelajahi menu</a></div>
    </main>
</body>
</html>
