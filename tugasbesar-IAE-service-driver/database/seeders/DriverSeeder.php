<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Driver;

class DriverSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Menyediakan beberapa driver dengan status tersedia (available) dan sibuk (busy)
        Driver::create([
            'name' => 'Rian Hidayat',
            'phone_number' => '081234567890',
            'status' => 'available'
        ]);

        Driver::create([
            'name' => 'Fikri Haikal',
            'phone_number' => '089876543210',
            'status' => 'available'
        ]);

        Driver::create([
            'name' => 'Eko Prasetyo',
            'phone_number' => '085112233445',
            'status' => 'busy'
        ]);
    }
}