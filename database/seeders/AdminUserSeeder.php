<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('ADMIN_EMAIL', 'admin@ohefe.test');
        $name = (string) env('ADMIN_NAME', 'Admin Ôhéfê');
        $password = (string) env('ADMIN_PASSWORD', 'password');

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password,
                'role' => UserRole::Admin,
                'is_admin' => true,
                'can_publish' => true,
            ],
        );

        $this->command?->info("Admin ready: {$admin->email} (Filament /admin)");
    }
}
