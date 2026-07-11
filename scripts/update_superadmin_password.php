<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Hash;

$emailCandidates = [
    'diabymadossou528@gmail.com',
    'diabymadousssou528@gmail.com',
];

$user = User::whereIn('email', $emailCandidates)->first();

if (! $user) {
    echo "No matching super admin user found.\n";
    exit(1);
}

$user->password = Hash::make('Super@26');
$user->save();

echo "Updated password for {$user->email}.\n";
