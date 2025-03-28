<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run()
    {
        DB::table('users')->updateOrInsert(
            ['email' => 'admin@admin.com'],
            [
                'name' => 'Admin',
                'password' => Hash::make('qazwsxedc'),
                'nik'=>"123123123123",
                'eklaim_key'=>"3286e120fea9b340164f0c76c50bbf0f529746666ce38e2d372dd2b4c5f0a946",
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
