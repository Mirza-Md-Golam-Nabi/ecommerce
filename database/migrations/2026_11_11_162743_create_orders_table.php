<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Money columns are poisha. See docs/02-database.md.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number', 20)->nullable()->unique();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('status', 30);
            $table->string('cancel_reason', 30)->nullable();
            $table->string('payment_method', 20)->default('cod');
            $table->string('payment_status', 20);
            $table->unsignedBigInteger('subtotal')->default(0);
            $table->unsignedBigInteger('delivery_charge')->default(0);
            $table->unsignedBigInteger('grand_total')->default(0);
            $table->unsignedBigInteger('collected_amount')->nullable();
            $table->unsignedBigInteger('cost_total')->default(0);
            $table->boolean('cost_is_final')->default(false);
            $table->unsignedBigInteger('unavailable_total')->default(0);
            $table->unsignedSmallInteger('items_count')->default(0);
            $table->string('shipping_name', 100);
            $table->string('shipping_phone', 11);
            $table->foreignId('area_id')->nullable()->constrained()->nullOnDelete();
            $table->string('area_name', 100);
            $table->string('address_line', 500);
            $table->text('customer_note')->nullable();
            $table->text('admin_note')->nullable();
            $table->timestamp('placed_at');
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('purchasing_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['status', 'placed_at']);
            $table->index(['customer_id', 'placed_at']);
            $table->index('delivered_at');
            $table->index(['area_id', 'delivered_at']);
            $table->index('shipping_phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
