<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminPasswordResetCommand extends Command
{
    protected $signature = 'app:admin-password-reset {email : Email akun admin lokal yang akan dipulihkan}';

    protected $description = 'Reset password akun admin pada lingkungan development lokal.';

    public function handle(): int
    {
        if (! $this->laravel->runningInConsole() || ! $this->laravel->environment('local')) {
            $this->error('Command ini hanya dapat dijalankan dari lingkungan Artisan dengan APP_ENV=local. Tidak ada perubahan yang dilakukan.');

            return self::FAILURE;
        }

        $email = mb_strtolower(trim((string) $this->argument('email')));
        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Format email tidak valid. Tidak ada perubahan yang dilakukan.');

            return self::FAILURE;
        }

        $matches = User::query()->whereRaw('LOWER(email) = ?', [$email])->limit(2)->get();
        if ($matches->count() !== 1) {
            $this->error($matches->isEmpty()
                ? 'Akun tidak ditemukan. Tidak ada perubahan yang dilakukan.'
                : 'Email tidak mengidentifikasi satu akun secara unik. Tidak ada perubahan yang dilakukan.');

            return self::FAILURE;
        }

        $account = $matches->first();
        if (! $account->isAdmin()) {
            $this->error('Akun tersebut bukan akun admin. Tidak ada perubahan yang dilakukan.');

            return self::FAILURE;
        }

        if (! $account->is_active) {
            $this->error('Akun admin tidak aktif. Tidak ada perubahan yang dilakukan.');

            return self::FAILURE;
        }

        $password = $this->secret('Password baru (input tersembunyi)', false);
        if (! is_string($password) || ! $this->isStrongPassword($password)) {
            $this->error('Password harus memiliki minimal 16 karakter, huruf besar dan kecil, angka, serta simbol. Tidak ada perubahan yang dilakukan.');

            return self::FAILURE;
        }

        $confirmation = $this->secret('Konfirmasi password baru', false);
        if (! is_string($confirmation) || ! hash_equals($password, $confirmation)) {
            $this->error('Konfirmasi password tidak cocok. Tidak ada perubahan yang dilakukan.');

            return self::FAILURE;
        }

        if (! $this->confirm('Simpan password baru untuk akun admin ini?', false)) {
            $this->info('Pemulihan dibatalkan. Tidak ada perubahan yang dilakukan.');

            return self::SUCCESS;
        }

        $updated = DB::transaction(function () use ($account, $email, $password): bool {
            $lockedAccount = User::query()->whereKey($account->getKey())->lockForUpdate()->first();

            if (! $lockedAccount
                || mb_strtolower($lockedAccount->email) !== $email
                || ! $lockedAccount->isAdmin()
                || ! $lockedAccount->is_active) {
                return false;
            }

            // Write only the password column; account identity and authorization fields stay untouched.
            DB::table('users')->where('id', $lockedAccount->getKey())->update([
                'password' => Hash::make($password),
                'updated_at' => now(),
            ]);

            return true;
        }, 3);

        if (! $updated) {
            $this->error('Identitas atau status akun berubah selama proses. Tidak ada perubahan yang dilakukan.');

            return self::FAILURE;
        }

        $this->info('Password akun admin berhasil diperbarui. Kredensial tidak ditampilkan.');

        return self::SUCCESS;
    }

    private function isStrongPassword(string $password): bool
    {
        return mb_strlen($password) >= 16
            && preg_match('/[a-z]/', $password) === 1
            && preg_match('/[A-Z]/', $password) === 1
            && preg_match('/[0-9]/', $password) === 1
            && preg_match('/[^A-Za-z0-9]/', $password) === 1;
    }
}
