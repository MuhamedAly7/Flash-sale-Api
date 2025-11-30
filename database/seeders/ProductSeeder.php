<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('products')->insert([
            'name'          => 'iPhone 15 Pro Max – Flash Sale Edition',
            'description'   => 'Limited 100 units – 50% off for the first 60 seconds',
            'price'         => 599.00,
            'total_stock'   => 100,
            'available_stock' => 100,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
    }
}
