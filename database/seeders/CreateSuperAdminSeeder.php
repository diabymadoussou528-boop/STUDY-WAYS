<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CreateSuperAdminSeeder extends Seeder
{
    /**
     * Seed the super admin account.
     * Uses firstOrCreate so it is safe to run multiple times.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'diabymadoussou528@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make(env('SUPER_ADMIN_PASSWORD', 'Super@26')),
                'role' => 'admin',
                'is_super_admin' => true,
                'is_active' => true,
            ]
        );
    }
}
