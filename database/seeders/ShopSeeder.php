<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Shop;
use App\Models\OrderList;
use App\Models\User;

class ShopSeeder extends Seeder
{
    public function run(): void
    {
        if (Shop::exists()) {
            return; // Prevent duplicate shop
        }

        // You may assign an admin user if needed
        $user = User::first();

        $shop = Shop::create();
        OrderList::create();
    }
}