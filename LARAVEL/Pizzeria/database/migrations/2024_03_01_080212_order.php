<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Driver;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('pizza_name');
            $table->decimal('unit_price', 8, 2);
            $table->integer('quantity');
            $table->string('address');
            $table->string('postal_code');
            $table->enum('status', ['preparing', 'delivering', 'delivered'])->default('preparing');
            $table->foreignIdFor(Driver::class)->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('customer_order', function (Blueprint $table) {
            $table->foreignIdFor(Customer::class);
            $table->foreignIdFor(Order::class);
            $table->primary(['customer_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_order');
        Schema::dropIfExists('orders');
    }
};
