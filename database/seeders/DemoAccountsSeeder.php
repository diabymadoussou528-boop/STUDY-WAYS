<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoAccountsSeeder extends Seeder
{
    /**
     * Ensure every role has a known, active login for local development.
     */
    public function run(): void
    {
        $accounts = [
            [
                'email' => 'student@studways.test',
                'name' => 'Demo Student',
                'role' => 'student',
                'password' => 'Douss123',
                'is_super_admin' => false,
            ],
            [
                'email' => 'professor@studways.test',
                'name' => 'Demo Professor',
                'role' => 'professor',
                'password' => 'Douss123',
                'is_super_admin' => false,
                'specialization' => 'Développement Web',
            ],
            [
                'email' => 'teacher@studways.test',
                'name' => 'Demo Teacher',
                'role' => 'professor',
                'password' => 'Douss123',
                'is_super_admin' => false,
                'specialization' => 'Développement Web',
            ],
            [
                'email' => 'admin@studways.test',
                'name' => 'Demo Admin',
                'role' => 'admin',
                'password' => 'Douss123',
                'is_super_admin' => false,
            ],
            [
                'email' => 'diabymadoussou528@gmail.com',
                'name' => 'Super Admin',
                'role' => 'admin',
                'password' => env('SUPER_ADMIN_PASSWORD', 'Douss123'),
                'is_super_admin' => true,
            ],
            [
                'email' => 'pierre@studways.test',
                'name' => 'Pierre Nikolaus',
                'role' => 'professor',
                'password' => 'Douss123',
                'is_super_admin' => false,
                'specialization' => 'Vue.js',
            ],
            [
                'email' => 'magnolia@studways.test',
                'name' => 'Magnolia Kub',
                'role' => 'professor',
                'password' => 'Douss123',
                'is_super_admin' => false,
                'specialization' => 'Laravel',
            ],
            [
                'email' => 'rita@studways.test',
                'name' => 'Rita Beatty PhD',
                'role' => 'professor',
                'password' => 'Douss123',
                'is_super_admin' => false,
                'specialization' => 'React',
            ],
        ];

        foreach ($accounts as $account) {
            $password = $account['password'];
            unset($account['password']);

            $user = User::query()->updateOrCreate(
                ['email' => $account['email']],
                [
                    ...$account,
                    'password' => $password,
                    'email_verified_at' => now(),
                    'is_active' => true,
                    'first_login' => false,
                ],
            );

            if (! Hash::check($password, $user->password)) {
                $user->forceFill(['password' => $password])->save();
            }
        }
    }
}
