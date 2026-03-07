<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {

            $table->id();

            $table->foreignId('order_list_id')
                ->constrained()
                ->cascadeOnDelete();

            // Business order identifier (visible to customer)
            $table->string('order_id', 7)->unique();

            // Order lifecycle
            $table->enum('order_status', [
                'Pending',
                'Confirmed',
                'Cancelled'
            ])->default('Pending');

            // Payment lifecycle
            $table->enum('payment_status', [
                'Unpaid',
                'Paid',
                'Failed'
            ])->default('Unpaid');

            // Payment method used
            $table->enum('payment_method', [
                'COD',
                'Online'
            ]);

            // Snapshot of ordered products
            $table->json('ordered_products')->nullable();

            // Snapshot of customer details
            $table->json('customer_details')->nullable();

            // Address snapshot
            $table->json('address')->nullable();

            $table->decimal('delivery_charge', 8, 2)->default(0);
            $table->decimal('total_price', 15, 2)->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};