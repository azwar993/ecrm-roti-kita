@extends('layouts.storefront')
@section('title', $article->seo_title ?: $article->title)
@section('meta_description', $article->seo_description ?: $article->excerpt)
@section('seo_title', $article->seo_title ?: $article->title)
@section('content')
<main class="rks-content-page rks-article-detail">
    <nav class="rks-article-breadcrumb" aria-label="Breadcrumb"><a class="rks-text-link" href="{{ route('home') }}">Beranda</a><span aria-hidden="true">/</span><a class="rks-text-link" href="{{ route('articles.index') }}">Artikel</a><span aria-hidden="true">/</span><span>{{ $article->category }}</span></nav>
    <article>
        <header class="rks-content-hero"><p class="rks-eyebrow">{{ $article->category }}</p><h1>{{ $article->title }}</h1><time datetime="{{ $article->published_at->toDateString() }}">{{ $article->published_at->translatedFormat('d F Y') }}</time>@if($article->excerpt)<p>{{ $article->excerpt }}</p>@endif</header>
        @if($article->cover_path && \Illuminate\Support\Facades\Storage::disk('public')->exists($article->cover_path))
            <img class="rks-article-detail-cover" src="{{ asset('storage/'.$article->cover_path) }}" alt="Cover {{ $article->title }}">
        @else
            <div class="rks-article-brand-cover" aria-label="Ilustrasi identitas RotiKita"><span>R</span><strong>RotiKita Bakery</strong><small>Roti Hangat Setiap Hari</small></div>
        @endif
        <div class="rks-article-body">
            @foreach(preg_split('/\R\s*\R/', trim($article->body)) as $block)
                @php($block = trim($block))
                @if($block === '') @continue @endif
                @if(str_starts_with($block, '## '))
                    <h2>{{ substr($block, 3) }}</h2>
                @elseif(preg_match('/^(?:- .+(?:\R|$))+/', $block))
                    <ul>@foreach(preg_split('/\R/', $block) as $item)<li>{{ ltrim($item, '- ') }}</li>@endforeach</ul>
                @else
                    <p>{{ $block }}</p>
                @endif
            @endforeach
        </div>
        @if($relatedArticles->isNotEmpty())
            <aside class="rks-related-articles"><h2>Artikel terkait</h2><ul>@foreach($relatedArticles as $relatedArticle)<li><a href="{{ route('articles.show', $relatedArticle) }}">{{ $relatedArticle->title }}</a></li>@endforeach</ul></aside>
        @endif
        <a class="rks-text-link rks-article-back" href="{{ route('articles.index') }}">← Kembali ke semua artikel</a>
    </article>
</main>
@endsection
