@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
    <div class="eyebrow" style="margin-bottom:12px">Bergabung dengan kami</div>
    <h2>Buat akun baru</h2>
    <p class="subtitle">Daftar untuk memesan produk dan memantau pesananmu dengan lebih mudah.</p>

    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="field">
            <label for="name">Nama lengkap</label>
            <input id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Nama kamu" autocomplete="name" maxlength="255" required autofocus>
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" required>
        </div>
        <div class="field">
            <label for="phone">Nomor telepon <span style="font-weight:400;color:var(--muted)">(opsional)</span></label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" autocomplete="tel" maxlength="30">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" minlength="8" required>
        </div>
        <div class="field">
            <label for="password_confirmation">Konfirmasi password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi password" autocomplete="new-password" required>
        </div>
        <div class="row-between" style="justify-content:flex-start;align-items:flex-start;line-height:1.5">
            <label class="check"><input type="checkbox" name="terms" value="1" required {{ old('terms') ? 'checked' : '' }}> Saya setuju data ini digunakan untuk kebutuhan akun dan layanan RotiKita.</label>
        </div>
        <button class="btn" type="submit">Buat akun <span aria-hidden="true">→</span></button>
    </form>
    <div class="form-foot">Sudah punya akun? <a href="{{ route('login') }}">Masuk</a></div>
@endsection
