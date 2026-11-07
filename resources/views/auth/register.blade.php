@extends('layouts.guest')

@section('title', 'Daftar')

@section('content')
    <a class="rk-auth-back-link" href="{{ route('home') }}"><span aria-hidden="true">←</span> Kembali ke beranda</a>
    <p class="rk-eyebrow rk-eyebrow-space">Mulai perjalananmu</p>
    <h2>Buat akun RotiKita</h2>
    <p class="rk-auth-subtitle">Cukup daftar sekali untuk memesan produk dan memantau pesananmu lebih mudah.</p>

    <form method="POST" action="{{ route('register.store') }}">
        @csrf
        <div class="rk-field">
            <label for="name">Nama lengkap</label>
            <input class="{{ $errors->has('name') ? 'is-invalid' : '' }}" id="name" name="name" type="text" value="{{ old('name') }}" placeholder="Nama kamu" autocomplete="name" maxlength="255" required autofocus>
            @error('name')<div class="rk-field-error">{{ $message }}</div>@enderror
        </div>
        <div class="rk-field">
            <label for="email">Alamat email</label>
            <input class="{{ $errors->has('email') ? 'is-invalid' : '' }}" id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" maxlength="255" required>
            @error('email')<div class="rk-field-error">{{ $message }}</div>@enderror
        </div>
        <div class="rk-field">
            <label for="phone">Nomor telepon <span class="rk-muted">(opsional)</span></label>
            <input id="phone" name="phone" type="tel" value="{{ old('phone') }}" placeholder="08xxxxxxxxxx" autocomplete="tel" maxlength="30">
            @error('phone')<div class="rk-field-error">{{ $message }}</div>@enderror
        </div>
        <div class="rk-field">
            <label for="password">Password</label>
            <div class="rk-password-wrap">
                <input class="{{ $errors->has('password') ? 'is-invalid' : '' }}" id="password" name="password" type="password" placeholder="Minimal 8 karakter" autocomplete="new-password" minlength="8" required>
                <button class="rk-password-toggle" type="button" data-password-toggle data-target="password" aria-controls="password" aria-label="Tampilkan password" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2.5 12s3.4-6.4 9.5-6.4 9.5 6.4 9.5 6.4-3.4 6.4-9.5 6.4S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                </button>
            </div>
            @error('password')<div class="rk-field-error">{{ $message }}</div>@enderror
        </div>
        <div class="rk-field">
            <label for="password_confirmation">Konfirmasi password</label>
            <div class="rk-password-wrap">
                <input id="password_confirmation" name="password_confirmation" type="password" placeholder="Ulangi password" autocomplete="new-password" minlength="8" required>
                <button class="rk-password-toggle" type="button" data-password-toggle data-target="password_confirmation" aria-controls="password_confirmation" aria-label="Tampilkan password" aria-pressed="false">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2.5 12s3.4-6.4 9.5-6.4 9.5 6.4 9.5 6.4-3.4 6.4-9.5 6.4S2.5 12 2.5 12Z"/><circle cx="12" cy="12" r="2.7"/></svg>
                </button>
            </div>
        </div>
        <div class="rk-field">
            <label class="rk-check"><input type="checkbox" name="terms" value="1" required @checked(old('terms'))> Saya setuju data akun digunakan untuk kebutuhan registrasi, pesanan, dan layanan RotiKita.</label>
            @error('terms')<div class="rk-field-error">{{ $message }}</div>@enderror
        </div>
        <button class="rk-btn rk-btn-block" type="submit">Buat akun <span aria-hidden="true">→</span></button>
    </form>

    <div class="rk-auth-form-foot">Sudah punya akun? <a class="rk-link" href="{{ route('login') }}">Masuk</a></div>
@endsection
