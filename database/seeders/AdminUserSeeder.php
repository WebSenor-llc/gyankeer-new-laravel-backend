<?php

namespace Database\Seeders;

use App\Models\UserRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        UserRole::updateOrCreate(
            ['login_email' => 'admin@websenor.com'],
            [
                'username'           => 'admin',
                'user_type'          => 'system',
                'role_name'          => 'Super Admin',
                'role_code'          => 'SUPER_ADMIN',
                'password'           => Hash::make('password'),
                'two_factor_enabled' => false,
                'login_attempts'     => 0,
                'locked_flag'        => false,
                'active_flag'        => true,
                'created_by'         => 'system',
            ]
        );
    }
}
