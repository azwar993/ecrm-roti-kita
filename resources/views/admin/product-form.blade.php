@extends('layouts.app')
@php($editing = $product->exists)
@section('title', $editing ? 'Edit Produk' : 'Tambah Produk')
@section('breadcrumb', 'Produk / '.($editing ? 'Edit' : 'Tambah'))
@section('content')
<div class="rk-page-heading rk-page-heading-v2">
    <div><p class="rk-eyebrow">Katalog toko · MySQL</p><h1>{{ $editing ? 'Edit produk' : 'Tambah produk' }}</h1><p>Perubahan yang disimpan akan langsung digunakan pada halaman manajemen dan katalog customer.</p></div>
    <a class="rk-btn rk-btn-quiet" href="{{ route('admin.products') }}">← Kembali ke produk</a>
</div>

@if($errors->any())
    <div class="rk-alert rk-alert-error" role="alert"><strong>Periksa data berikut:</strong><ul class="rk-real-error-list">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<section class="rk-panel rk-panel-v2 rk-real-form-panel">
    <form method="POST" action="{{ $editing ? route('admin.products.update', $product) : route('admin.products.store') }}" enctype="multipart/form-data">
        @csrf
        @if($editing) @method('PUT') @endif
        <div class="rk-real-form-layout">
            <div class="rk-real-form-fields">
                <div class="rk-real-form-grid">
                    <label class="rk-field"><span>Nama produk *</span><input type="text" name="name" value="{{ old('name', $product->name) }}" maxlength="150" required placeholder="Contoh: Roti Cokelat"><small class="rk-real-help">Nama yang akan terlihat oleh pelanggan.</small></label>
                    <label class="rk-field"><span>SKU / kode produk *</span><input type="text" name="sku" value="{{ old('sku', $product->sku) }}" maxlength="50" required autocomplete="off"><small class="rk-real-help">Masukkan SKU yang sudah diperiksa dan disetujui. SKU harus unik.</small></label>
                    <label class="rk-field"><span>Kategori *</span><select name="category" required><option value="">Pilih kategori</option>@foreach($categoryOptions as $option)<option value="{{ $option }}" @selected(old('category', $product->category) === $option)>{{ $option }}</option>@endforeach</select><small class="rk-real-help">Pilih kategori bakery yang sesuai. Kategori lama tetap tersedia agar data produk tidak berubah.</small></label>
                    <label class="rk-field"><span>Harga jual (Rp) *</span><input type="number" name="price" value="{{ old('price', $product->price) }}" min="1" step="1" required><small class="rk-real-help">Masukkan harga yang telah disetujui.</small></label>
                    <label class="rk-field"><span>Satuan produk</span><input type="text" name="unit" value="{{ old('unit', $product->unit) }}" maxlength="30" placeholder="Contoh: pcs, loaf, cup"></label>
                    <label class="rk-field"><span>Status harga *</span><select name="price_status" required><option value="final" @selected(old('price_status', $product->price_status ?? 'final') === 'final')>Final / disetujui</option><option value="proposed" @selected(old('price_status', $product->price_status ?? 'final') === 'proposed')>Usulan / perlu persetujuan</option></select></label>
                    <label class="rk-field rk-real-full"><span>Catatan admin</span><textarea name="admin_note" rows="3" maxlength="2000">{{ old('admin_note', $product->admin_note) }}</textarea></label>
                    <label class="rk-field"><span>Stok aktual *</span><input type="number" name="stock_qty" value="{{ old('stock_qty', $product->stock_qty ?? 0) }}" min="0" step="1" required><small class="rk-real-help">Masukkan jumlah stok yang benar-benar tersedia.</small></label>
                    <label class="rk-field"><span>Status produk</span><select name="is_active"><option value="0" @selected((string)old('is_active', $product->exists ? (int)$product->is_active : 0) === '0')>Draft / nonaktif — disembunyikan</option><option value="1" @selected((string)old('is_active', $product->exists ? (int)$product->is_active : 0) === '1')>Aktif — tampil dan dapat dipesan</option></select><small class="rk-real-help">Produk aktif wajib memiliki foto tersimpan yang valid.</small></label>
                    <label class="rk-field rk-real-full"><span>Deskripsi produk</span><textarea name="description" rows="4" maxlength="3000" placeholder="Komposisi, ukuran, rasa, atau informasi produk…">{{ old('description', $product->description) }}</textarea></label>
                </div>
            </div>
            <aside class="rk-real-photo-panel">
                <div class="rk-real-photo-heading"><strong>Foto produk aktual</strong><small>JPG, JPEG, PNG, atau WEBP · maks. 4 MB</small></div>
                <div class="rk-real-photo-preview" id="product-photo-preview" data-product-photo-preview>
                    <div class="rk-real-photo-empty" id="product-photo-placeholder" @if($product->image_path) hidden @endif><span>＋</span><strong>Belum ada foto</strong><small>Foto asli akan tampil di katalog.</small></div>
                    <img src="{{ $product->image_path ? asset('storage/'.ltrim($product->image_path, '/')) : '' }}" alt="{{ $product->image_path ? 'Foto '.$product->name : 'Preview foto produk' }}" id="product-photo-preview-image" data-product-preview-image @if(!$product->image_path) hidden @endif>
                </div>
                <label class="rk-field"><span>{{ $editing ? 'Unggah foto baru (opsional)' : 'Pilih foto produk' }}</span><input id="product-image-input" type="file" name="image" accept="image/jpeg,image/png,image/webp" data-product-image-input></label>
                <p class="rk-real-help" id="product-image-message" role="status" aria-live="polite">Pilih file foto dari komputer. Foto disimpan di storage lokal aplikasi.</p>
                @if($editing && $product->image_path)
                    <label class="rk-real-remove-photo"><input type="checkbox" name="remove_image" value="1" data-remove-product-image disabled @checked(old('remove_image'))> Hapus foto saat ini</label>
                    <small class="rk-real-help">Pilih foto baru untuk mengganti. Penghapusan foto akan diminta konfirmasi.</small>
                @endif
                <div class="rk-real-photo-note">Gunakan foto aktual RotiKita yang telah disediakan dan disetujui tim. Foto stok ilustrasi tidak boleh dipakai sebagai foto produk aktual.</div>
            </aside>
        </div>
        <div class="rk-real-form-footer"><a class="rk-btn rk-btn-quiet" href="{{ route('admin.products') }}">Batal</a><button class="rk-btn" type="submit">{{ $editing ? 'Simpan perubahan' : 'Simpan produk' }}</button></div>
    </form>
</section>
<script src="{{ asset('js/admin-product-form.js') }}?v={{ filemtime(public_path('js/admin-product-form.js')) }}" defer></script>
@endsection
