@extends('layouts.storefront')
@section('title', 'Cerita dari Dapur RotiKita')
@section('content')
<main class="rks-content-page">
    <header class="rks-content-hero"><p class="rks-eyebrow">Inspirasi bakery</p><h1>Cerita dari Dapur <em>RotiKita</em></h1><p>Tips, cerita proses, dan kabar terbaru dari bakery kami.</p></header>
    @if($articles->isNotEmpty())
        <div class="rks-article-grid">
            @foreach($articles as $article)
                <article class="rks-article-card">
                    <a href="{{ route('articles.show', $article) }}" class="rks-article-cover">@if($article->display_cover_path)<img data-store-image src="{{ asset('storage/'.$article->display_cover_path) }}" alt="{{ $article->cover_alt }}" loading="lazy"><span data-store-image-fallback class="rks-article-brand-tile" aria-hidden="true" hidden>RotiKita Bakery</span>@else<span class="rks-article-brand-tile" aria-hidden="true">RotiKita Bakery</span>@endif</a>
                    <div class="rks-article-card-copy"><span class="rks-article-category">{{ $article->category }}</span><time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->translatedFormat('d F Y') }}</time><h2><a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a></h2><p>{{ $article->excerpt ?: \Illuminate\Support\Str::limit($article->body, 150) }}</p><a class="rks-text-link" href="{{ route('articles.show', $article) }}">Baca selengkapnya <span aria-hidden="true">→</span></a></div>
                </article>
            @endforeach
        </div>
        {{ $articles->links() }}
    @else
        <div class="rks-content-empty"><h2>Belum ada cerita yang diterbitkan</h2><p>Artikel RotiKita akan hadir di sini saat sudah siap dibagikan.</p><a class="rks-btn rks-btn-primary" href="{{ route('storefront.menu') }}">Jelajahi menu</a></div>
    @endif
</main>
@endsection
