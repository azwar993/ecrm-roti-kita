@props([
    'eyebrow' => 'Modul CRM',
    'title' => 'Modul sedang disiapkan',
    'description' => 'Halaman ini sudah memiliki kerangka UI. Logika bisnis dan koneksi database akan diimplementasikan pada tahap berikutnya.',
    'icon' => '✳',
    'items' => [],
])

<div class="rk-page-heading">
    <div>
        <p class="rk-eyebrow">{{ $eyebrow }}</p>
        <h1>{{ $title }}</h1>
        <p>{{ $description }}</p>
    </div>
    <span class="rk-status rk-status-soon">UI siap · Backend menyusul</span>
</div>

<section class="rk-panel rk-mb-18">
    <div class="rk-empty-state">
        <div class="rk-empty-icon" aria-hidden="true">{{ $icon }}</div>
        <h3>Kerangka halaman sudah siap</h3>
        <p>Halaman ini belum mengubah atau menampilkan data transaksi nyata. Setelah rancangan disepakati, kita akan menambahkan validasi, controller, model, serta operasi database yang sesuai.</p>
    </div>
</section>

@if (count($items))
    <section class="rk-panel">
        <div class="rk-panel-heading">
            <div><h2>Ruang lingkup modul</h2><p>Fitur yang akan dikerjakan pada tahap berikutnya.</p></div>
        </div>
        <div class="rk-page-grid">
            @foreach ($items as $item)
                <div class="rk-feature-card">
                    <span class="rk-quick-icon" aria-hidden="true">✓</span>
                    <div><h3>{{ $item }}</h3><p>Disiapkan mengikuti rancangan E-CRM RotiKita.</p></div>
                </div>
            @endforeach
        </div>
    </section>
@endif
