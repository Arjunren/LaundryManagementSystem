<?php

namespace Database\Seeders;

use App\Models\ServiceType;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::create(['name' => 'Laundry Admin', 'email' => 'admin@example.com', 'password' => 'ChangeMe123!', 'role' => 'admin', 'active' => true]);
        User::create(['name' => 'Demo Customer', 'email' => 'customer@example.com', 'password' => 'ChangeMe123!', 'role' => 'customer', 'active' => true, 'phone' => '+10000000000']);
        ServiceType::create(['name' => 'Wash and Fold', 'unit' => 'kg', 'price_per_unit' => 3.50, 'turnaround_hours' => 48, 'active' => true]);
        ServiceType::create(['name' => 'Dry Cleaning', 'unit' => 'item', 'price_per_unit' => 8.00, 'turnaround_hours' => 72, 'active' => true]);
    }
}
