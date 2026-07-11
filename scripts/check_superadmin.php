<?php

require __DIR__.'/../vendor/autoload.php';
// Bootstrap the framework properly
$app = require __DIR__.'/../bootstrap/app.php';
// Kernel contract exists in Illuminate\Contracts\Console\Kernel
use Illuminate\Contracts\Console\Kernel;

$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\User;

$user = User::where('email', 'diabymadoussou528@gmail.com')
    ->orWhere('email', 'diabymadossou528@gmail.com')
    ->first();

if (! $user) {
    echo "No super admin user found.\n";
    exit(1);
}

echo "email: {$user->email}\n";
echo "role: {$user->role}\n";
echo 'is_super_admin: '.($user->is_super_admin ? '1' : '0')."\n";
echo 'is_active: '.($user->is_active ? '1' : '0')."\n";
