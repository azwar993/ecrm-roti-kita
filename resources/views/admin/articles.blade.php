@extends('layouts.app')
@section('title', 'Artikel')
@section('breadcrumb', 'Artikel')
@section('content')
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Cerita dari dapur</p><h1>Artikel RotiKita</h1><p>Kelola tulisan yang akan muncul di storefront.</p></div><a class="rk-btn" href="{{ route('admin.articles.create') }}">Tulis artikel</a></div>
<section class="rk-panel rk-panel-v2">
    <form method="GET" class="rk-real-form-grid">
        <label class="rk-field"><span>Cari artikel</span><input name="q" value="{{ $search }}" placeholder="Judul atau slug"></label>
        <label class="rk-field"><span>Status</span><select name="status"><option value="all" @selected($status === 'all')>Semua</option><option value="draft" @selected($status === 'draft')>Draft</option><option value="published" @selected($status === 'published')>Published</option></select></label>
        <div class="rk-real-form-footer"><button class="rk-btn" type="submit">Terapkan</button></div>
    </form>
    @forelse ($articles as $article)
        <article class="rk-admin-article-row">
            <div><span class="rk-commerce-pill {{ $article->status === 'published' ? 'green' : 'orange' }}">{{ $article->status === 'published' ? 'Terbit' : 'Draft' }}</span><h2>{{ $article->title }}</h2><p>/artikel/{{ $article->slug }} @if($article->published_at)· {{ $article->published_at->format('d/m/Y H:i') }}@endif</p></div>
            <div class="rk-commerce-actions"><a class="rk-btn rk-btn-quiet" href="{{ route('admin.articles.edit', $article) }}">Edit</a><form method="POST" action="{{ route('admin.articles.destroy', $article) }}" onsubmit="return confirm('Hapus artikel ini?')">@csrf @method('DELETE')<button class="rk-btn rk-btn-quiet" type="submit">Hapus</button></form></div>
        </article>
    @empty
        <div class="rks-content-empty"><h3>Belum ada artikel</h3><p>Tulisan yang dibuat admin akan muncul dalam daftar ini.</p></div>
    @endforelse
    {{ $articles->links() }}
</section>
@endsection
