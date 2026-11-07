@extends('layouts.app')
@section('title', $article->exists ? 'Edit Artikel' : 'Tulis Artikel')
@section('breadcrumb', 'Artikel')
@section('content')
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Cerita dari dapur</p><h1>{{ $article->exists ? 'Edit artikel' : 'Tulis artikel' }}</h1></div><a class="rk-btn rk-btn-quiet" href="{{ route('admin.articles.index') }}">Kembali</a></div>
<section class="rk-panel rk-panel-v2 rk-article-editor">
    @if ($errors->any())<div class="rk-commerce-errors" role="alert"><ul>@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
    <form method="POST" enctype="multipart/form-data" action="{{ $article->exists ? route('admin.articles.update', $article) : route('admin.articles.store') }}">
        @csrf
        @if($article->exists) @method('PUT') @endif
        <div class="rk-real-form-grid">
            <label class="rk-field"><span>Judul</span><input name="title" maxlength="180" required value="{{ old('title', $article->title) }}"></label>
            <label class="rk-field"><span>Slug (opsional)</span><input name="slug" maxlength="200" value="{{ old('slug', $article->slug) }}" placeholder="Dibuat dari judul bila kosong"></label>
            <label class="rk-field rk-span-2"><span>Ringkasan</span><textarea name="excerpt" rows="3" maxlength="1000">{{ old('excerpt', $article->excerpt) }}</textarea></label>
            <label class="rk-field"><span>Kategori</span><select name="category" required>@foreach(['Tips Bakery', 'Edukasi Bakery', 'Inspirasi', 'Tips Roti', 'Tips Dapur'] as $category)<option value="{{ $category }}" @selected(old('category', $article->category) === $category)>{{ $category }}</option>@endforeach</select></label>
            <label class="rk-field"><span>SEO title</span><input name="seo_title" maxlength="180" value="{{ old('seo_title', $article->seo_title) }}"></label>
            <label class="rk-field rk-span-2"><span>SEO description</span><textarea name="seo_description" rows="2" maxlength="320">{{ old('seo_description', $article->seo_description) }}</textarea></label>
            <label class="rk-field rk-span-2"><span>Isi artikel</span><textarea name="body" rows="14" maxlength="50000" required>{{ old('body', $article->body) }}</textarea><small>Konten berupa teks biasa. HTML tidak dirender.</small></label>
            <label class="rk-field"><span>Cover (JPG, PNG, WEBP; maks. 4 MB)</span><input type="file" name="cover" accept=".jpg,.jpeg,.png,.webp"></label>
            <label class="rk-field"><span>Status</span><select name="status"><option value="draft" @selected(old('status', $article->status ?: 'draft') === 'draft')>Draft</option><option value="published" @selected(old('status', $article->status) === 'published')>Published</option></select></label>
            <label class="rk-field"><span>Tanggal publikasi</span><input type="datetime-local" name="published_at" value="{{ old('published_at', $article->published_at?->format('Y-m-d\TH:i')) }}"><small>Jika published dan kosong, waktu saat simpan digunakan.</small></label>
            @if($article->cover_path)<div class="rk-field"><span>Cover tersimpan</span><img class="rk-article-admin-cover" src="{{ asset('storage/'.$article->cover_path) }}" alt="Cover artikel saat ini"></div>@endif
        </div>
        <div class="rk-real-form-footer"><a class="rk-btn rk-btn-quiet" href="{{ route('admin.articles.index') }}">Batal</a><button class="rk-btn" type="submit">Simpan artikel</button></div>
    </form>
</section>
@endsection
