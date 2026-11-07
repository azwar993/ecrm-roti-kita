<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordResetCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_local_admin_password_reset_updates_only_the_password_after_confirmation(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $admin = User::factory()->create([
            'name' => 'Development Admin',
            'email' => 'admin@rotikita.test',
            'role' => 'admin',
            'is_active' => true,
            'password' => 'old-admin-password',
        ]);
        $newPassword = 'RotiKita-Dev-2026!Secure';

        $this->artisan('app:admin-password-reset', ['email' => $admin->email])
            ->expectsQuestion('Password baru (input tersembunyi)', $newPassword)
            ->expectsQuestion('Konfirmasi password baru', $newPassword)
            ->expectsConfirmation('Simpan password baru untuk akun admin ini?', 'yes')
            ->expectsOutputToContain('Password akun admin berhasil diperbarui.')
            ->doesntExpectOutputToContain($newPassword)
            ->assertExitCode(0);

        $updated = $admin->fresh();
        $this->assertTrue(Hash::check($newPassword, $updated->password));
        $this->assertSame('Development Admin', $updated->name);
        $this->assertSame('admin@rotikita.test', $updated->email);
        $this->assertSame('admin', $updated->role);
        $this->assertTrue($updated->is_active);
    }

    public function test_password_reset_refuses_non_local_environment_without_modifying_account(): void
    {
        config(['app.env' => 'testing']);
        $admin = User::factory()->create([
            'email' => 'admin@rotikita.test',
            'role' => 'admin',
            'is_active' => true,
            'password' => 'old-admin-password',
        ]);
        $oldHash = $admin->getRawOriginal('password');

        $this->artisan('app:admin-password-reset', ['email' => $admin->email])
            ->expectsOutputToContain('APP_ENV=local')
            ->assertExitCode(1);

        $this->assertSame($oldHash, $admin->fresh()->getRawOriginal('password'));
    }

    public function test_password_reset_refuses_customer_and_inactive_admin_accounts(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $customer = User::factory()->create([
            'email' => 'customer@rotikita.test', 'role' => 'customer', 'is_active' => true,
            'password' => 'old-customer-password',
        ]);
        $inactiveAdmin = User::factory()->create([
            'email' => 'inactive-admin@rotikita.test', 'role' => 'admin', 'is_active' => false,
            'password' => 'old-admin-password',
        ]);
        $customerHash = $customer->getRawOriginal('password');
        $adminHash = $inactiveAdmin->getRawOriginal('password');

        $this->artisan('app:admin-password-reset', ['email' => $customer->email])
            ->expectsOutputToContain('bukan akun admin')
            ->assertExitCode(1);
        $this->artisan('app:admin-password-reset', ['email' => $inactiveAdmin->email])
            ->expectsOutputToContain('tidak aktif')
            ->assertExitCode(1);

        $this->assertSame($customerHash, $customer->fresh()->getRawOriginal('password'));
        $this->assertSame($adminHash, $inactiveAdmin->fresh()->getRawOriginal('password'));
    }

    public function test_password_reset_rejects_weak_or_mismatched_password_without_writing(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $admin = User::factory()->create([
            'email' => 'admin@rotikita.test', 'role' => 'admin', 'is_active' => true,
            'password' => 'old-admin-password',
        ]);
        $oldHash = $admin->getRawOriginal('password');

        $this->artisan('app:admin-password-reset', ['email' => $admin->email])
            ->expectsQuestion('Password baru (input tersembunyi)', 'weak-password')
            ->expectsOutputToContain('minimal 16 karakter')
            ->assertExitCode(1);

        $this->artisan('app:admin-password-reset', ['email' => $admin->email])
            ->expectsQuestion('Password baru (input tersembunyi)', 'RotiKita-Dev-2026!Secure')
            ->expectsQuestion('Konfirmasi password baru', 'RotiKita-Dev-2026!Different')
            ->expectsOutputToContain('Konfirmasi password tidak cocok')
            ->assertExitCode(1);

        $this->assertSame($oldHash, $admin->fresh()->getRawOriginal('password'));
    }

    public function test_password_reset_can_be_cancelled_at_the_final_confirmation(): void
    {
        app()->detectEnvironment(fn () => 'local');
        $admin = User::factory()->create([
            'email' => 'admin@rotikita.test', 'role' => 'admin', 'is_active' => true,
            'password' => 'old-admin-password',
        ]);
        $oldHash = $admin->getRawOriginal('password');

        $this->artisan('app:admin-password-reset', ['email' => $admin->email])
            ->expectsQuestion('Password baru (input tersembunyi)', 'RotiKita-Dev-2026!Secure')
            ->expectsQuestion('Konfirmasi password baru', 'RotiKita-Dev-2026!Secure')
            ->expectsConfirmation('Simpan password baru untuk akun admin ini?', 'no')
            ->expectsOutputToContain('Pemulihan dibatalkan')
            ->assertExitCode(0);

        $this->assertSame($oldHash, $admin->fresh()->getRawOriginal('password'));
    }

    public function test_password_reset_does_not_create_an_account_for_an_unknown_email(): void
    {
        app()->detectEnvironment(fn () => 'local');

        $this->artisan('app:admin-password-reset', ['email' => 'missing@rotikita.test'])
            ->expectsOutputToContain('Akun tidak ditemukan')
            ->assertExitCode(1);

        $this->assertDatabaseMissing('users', ['email' => 'missing@rotikita.test']);
    }
}
