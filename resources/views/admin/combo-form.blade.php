@extends('layouts.app')
@section('title', $combo->exists ? 'Edit Combo' : 'Buat Combo')
@section('breadcrumb', 'Combo')
@section('content')
@php
    $componentRows = old('items', $combo->exists ? $combo->items->map(fn($item)=>['product_id'=>$item->product_id,'quantity'=>$item->quantity])->all() : [['product_id'=>'','quantity'=>1]]);
@endphp
<div class="rk-page-heading rk-page-heading-v2"><div><p class="rk-eyebrow">Paket produk</p><h1>{{ $combo->exists ? 'Edit combo' : 'Buat combo' }}</h1><p>Harga paket dapat berbeda dari total harga eceran komponennya. Stok tetap mengikuti produk penyusun.</p></div><a class="rk-btn rk-btn-quiet" href="{{ route('admin.combos.index') }}">Kembali</a></div>
<section class="rk-panel rk-panel-v2"><form method="POST" enctype="multipart/form-data" action="{{ $combo->exists ? route('admin.combos.update',$combo) : route('admin.combos.store') }}">@csrf @if($combo->exists) @method('PUT') @endif
<div class="rk-real-form-grid">
<label class="rk-field"><span>Nama combo</span><input name="name" value="{{ old('name',$combo->name) }}" maxlength="150" required>@error('name')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>SKU</span><input name="sku" value="{{ old('sku',$combo->sku) }}" maxlength="50" required>@error('sku')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>Harga paket (Rp)</span><input type="number" name="price" min="0.01" step="0.01" value="{{ old('price',$combo->price) }}" required>@error('price')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>Foto combo (opsional)</span><input type="file" name="image" accept=".jpg,.jpeg,.png,.webp">@error('image')<small class="rk-field-error">{{ $message }}</small>@enderror @if($combo->image_path)<small>Foto saat ini: {{ $combo->image_path }}</small>@endif</label>
<label class="rk-field"><span>Mulai (opsional)</span><input type="datetime-local" name="starts_at" value="{{ old('starts_at',$combo->starts_at?->format('Y-m-d\TH:i')) }}">@error('starts_at')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field"><span>Berakhir (opsional)</span><input type="datetime-local" name="ends_at" value="{{ old('ends_at',$combo->ends_at?->format('Y-m-d\TH:i')) }}">@error('ends_at')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
<label class="rk-field rk-span-2"><span>Deskripsi (opsional)</span><textarea name="description" rows="3" maxlength="3000">{{ old('description',$combo->description) }}</textarea>@error('description')<small class="rk-field-error">{{ $message }}</small>@enderror</label>
</div>
<div class="rk-panel-heading"><div><h2>Produk penyusun</h2><p>Setiap produk hanya dicantumkan satu kali; tentukan jumlah per combo.</p></div><button class="rk-btn rk-btn-small" type="button" id="add-component">Tambah produk</button></div>
@error('items')<div class="rk-field-error">{{ $message }}</div>@enderror
<div id="combo-components">@foreach($componentRows as $index=>$row)<div class="rk-real-form-grid combo-component-row"><label class="rk-field"><span>Produk</span><select name="items[{{ $index }}][product_id]" required><option value="">Pilih produk</option>@foreach($products as $product)<option value="{{ $product->id }}" @selected((string)$row['product_id']===(string)$product->id)>{{ $product->name }}{{ $product->is_active?'':' (nonaktif)' }}</option>@endforeach</select>@error("items.$index.product_id")<small class="rk-field-error">{{ $message }}</small>@enderror</label><label class="rk-field"><span>Jumlah per combo</span><input type="number" name="items[{{ $index }}][quantity]" min="1" max="9999" value="{{ $row['quantity'] }}" required>@error("items.$index.quantity")<small class="rk-field-error">{{ $message }}</small>@enderror</label><button class="rk-real-danger remove-component" type="button" @disabled(count($componentRows)===1)>Hapus baris</button></div>@endforeach</div>
@error('is_active')<small class="rk-field-error">{{ $message }}</small>@enderror<label class="rk-check"><input type="checkbox" name="is_active" value="1" @checked(old('is_active',$combo->exists?$combo->is_active:true))> Aktif dan dapat dijual</label>
<template id="component-template"><div class="rk-real-form-grid combo-component-row"><label class="rk-field"><span>Produk</span><select data-component-product required><option value="">Pilih produk</option>@foreach($products as $product)<option value="{{ $product->id }}" @disabled(!$product->is_active)>{{ $product->name }}{{ $product->is_active?'':' (nonaktif)' }}</option>@endforeach</select></label><label class="rk-field"><span>Jumlah per combo</span><input data-component-quantity type="number" min="1" max="9999" value="1" required></label><button class="rk-real-danger remove-component" type="button">Hapus baris</button></div></template>
<div class="rk-real-form-footer"><a class="rk-btn rk-btn-quiet" href="{{ route('admin.combos.index') }}">Batal</a><button class="rk-btn" type="submit">Simpan combo</button></div></form></section>
<script>
(() => {
    const list = document.getElementById('combo-components');
    const add = document.getElementById('add-component');
    const template = document.getElementById('component-template');
    let nextIndex = {{ count($componentRows) }};
    add.addEventListener('click', () => {
        const fragment = template.content.cloneNode(true);
        fragment.querySelector('[data-component-product]').name = `items[${nextIndex}][product_id]`;
        fragment.querySelector('[data-component-quantity]').name = `items[${nextIndex}][quantity]`;
        nextIndex++;
        list.appendChild(fragment);
        syncRemoveButtons();
    });
    function syncRemoveButtons() {
        const rows = list.querySelectorAll('.combo-component-row');
        rows.forEach(row => row.querySelector('.remove-component').disabled = rows.length === 1);
    }
    list.addEventListener('click', event => {
        const button = event.target.closest('.remove-component');
        if (button && list.querySelectorAll('.combo-component-row').length > 1) {
            button.closest('.combo-component-row').remove();
            syncRemoveButtons();
        }
    });
    syncRemoveButtons();
})();
</script>
@endsection
