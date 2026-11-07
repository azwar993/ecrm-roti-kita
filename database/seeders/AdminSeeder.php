<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdminSeeder extends Seeder
{
    /** Create a local development admin account without changing an existing account. */
    public function run(): void
    {
        $email = strtolower(trim((string) env('ROTIKITA_ADMIN_EMAIL', 'admin@rotikita.test')));
        $password = env('ROTIKITA_ADMIN_PASSWORD');
        $name = trim((string) env('ROTIKITA_ADMIN_NAME', 'Admin RotiKita'));

        if (! is_string($password) || strlen($password) < 16) {
            throw new RuntimeException('Set ROTIKITA_ADMIN_PASSWORD to a unique password of at least 16 characters before running this seeder.');
        }

        $created = DB::transaction(function () use ($email, $name, $password): bool {
            $existing = User::query()->where('email', $email)->lockForUpdate()->first();

            if ($existing) {
                if (! $existing->isAdmin()) {
                    throw new RuntimeException("An account already uses {$email} with a non-admin role; no changes were made.");
                }

                return false;
            }

            $user = new User([
                'name' => $name,
                'email' => $email,
                'password' => $password,
            ]);
            $user->role = 'admin';
            $user->is_active = true;
            // User's hashed cast applies Laravel's configured password hasher.
            $user->save();

            return true;
        });

        $this->command?->info($created
            ? "Development admin created: {$email}"
            : "Existing admin left unchanged: {$email}");
    }
}
