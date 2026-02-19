<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'bidkhong.app@gmail.com'],
            [
                'name' => 'BidKhong Admin',
                'password' => 'admin1234',
                'phone_number' => '0000000000',
                'role' => 'admin',
            ]
        );

        // สร้าง wallet ให้ admin ถ้ายังไม่มี
        if (!$admin->wallet) {
            $admin->wallet()->create([
                'balance_available' => 0,
                'balance_total' => 0,
                'balance_pending' => 0,
                'withdraw' => 0,
                'deposit' => 0,
            ]);
        }
    }
}
