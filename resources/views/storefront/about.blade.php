@extends('layouts.storefront')
@section('title', 'Tentang Kami')
@section('content')
<main class="rks-content-page">
    <header class="rks-content-hero"><p class="rks-eyebrow">RotiKita Bakery</p><h1>Roti Hangat <em>Setiap Hari</em></h1><p>Kami menyajikan pilihan bakery untuk menemani hari, dibuat dan dikelola dengan perhatian pada kualitas bahan, kesegaran, dan pengalaman pelanggan.</p></header>
    <section class="rks-about-panel"><h2>Dari dapur kami untuk harimu</h2><p>Jelajahi menu RotiKita, pesan secara online, dan ikuti cerita serta kabar terbaru dari bakery kami.</p><a class="rks-btn rks-btn-primary" href="{{ route('storefront.menu') }}">Lihat menu bakery</a></section>
</main>
@endsection
