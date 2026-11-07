@extends('layouts.guest')

@section('title', 'Masuk')

@section('content')
    <div class="eyebrow" style="margin-bottom:12px">Selamat datang kembali</div>
    <h2>Masuk ke akunmu</h2>
    <p class="subtitle">Lanjutkan pengalamanmu bersama RotiKita.</p>

    <form method="POST" action="{{ route('login.store') }}">
        @csrf
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" placeholder="nama@email.com" autocomplete="email" required autofocus>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input id="password" name="password" type="password" placeholder="Masukkan password" autocomplete="current-password" required>
        </div>
        <div class="row-between">
            <label class="check"><input type="checkbox" name="remember" value="1"> Ingat saya</label>
            <span>Amankan password kamu</span>
        </div>
        <button class="btn" type="submit">Masuk ke RotiKita <span aria-hidden="true">→</span></button>
    </form>
    <div class="form-foot">Belum punya akun? <a href="{{ route('register') }}">Daftar sekarang</a></div>
    <p class="small-note">Dengan masuk, kamu menyetujui penggunaan data akun untuk kebutuhan layanan dan pengelolaan pesanan RotiKita.</p>
@endsection
