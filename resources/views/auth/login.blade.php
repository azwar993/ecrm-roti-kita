@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <a class="rk-auth-back-link" href="{{ route('home') }}"><span aria-hidden="true">←</span> Kembali ke beranda</a>
    <p class="rk-eyebrow rk-eyebrow-space">Senang melihatmu lagi</p>
    <h2>Masuk ke akunmu</h2>
    <p class="rk-auth-subtitle">Lanjutkan pesanan dan temukan lagi roti favoritmu di RotiKita.</p>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="rk-field">
            <label for="email">Alamat email</label>
            <input class="{{ $errors->has('email') ? 'is-invalid' : '' }}" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="username" maxlength="255" required autofocus>
            @error('email')<div class="rk-field-error">{{ $message }}</div>@enderror
        </div>
        <div class="rk-field">
            <label for="password">Password</label>
            <div class="rk-password-wrap">
                <input class="{{ $errors->has('password') ? 'is-invalid' : '' }}" id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required>
                <button class="rk-password-toggle" type="button" data-password-toggle data-target="password" aria-controls="password" aria-label="Tampilkan password" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2.5 12s3.4-6.4 9.5-6.4 9.5 6.4 9.5 6.4-3.4 6.4-9.5 6.4S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                </button>
            </div>
            @error('password')<div class="rk-field-error">{{ $message }}</div>@enderror
        </div>
        <div class="rk-auth-row">
            <label class="rk-check"><input type="checkbox" name="remember" value="1" @checked(old('remember'))> Ingat saya</label>
            <span class="rk-kicker">Password kamu tetap pribadi</span>
        </div>
        <button class="rk-btn rk-btn-block" type="submit">Masuk ke RotiKita <span aria-hidden="true">→</span></button>
    </form>

    <div class="rk-auth-form-foot">Belum punya akun? <a class="rk-link" href="{{ route('register') }}">Daftar sekarang</a></div>
    <p class="rk-auth-note">Informasi akun digunakan untuk mengelola akses, pesanan, dan layanan pelanggan RotiKita.</p>
@endsection
