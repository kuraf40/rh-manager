<?php

namespace Database\Seeders;

use App\Models\OvertimeParameter;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->firstOrCreate(['email' => 'admin@example.com'], [
            'name' => 'Administrateur',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        OvertimeParameter::query()->firstOrCreate(['id' => 1], [
            'hs_12' => 10.00,
            'hs_35' => 35.00,
            'hs_50' => 50.00,
            'hs_100' => 100.00,
        ]);
    }
}
