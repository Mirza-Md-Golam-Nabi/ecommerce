<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Product data is snapshotted; rows are never deleted (unavailable items keep a status).
     */
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->restrictOnDelete();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('product_name', 200);
            $table->string('unit_label', 50);
            $table->string('sale_type', 20);
            $table->unsignedInteger('nominal_weight_grams')->nullable();
            $table->unsignedBigInteger('unit_price');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedSmallInteger('fulfilled_quantity')->nullable();
            $table->unsignedInteger('actual_weight_grams')->nullable();
            $table->unsignedBigInteger('line_total');
            $table->unsignedBigInteger('estimated_unit_cost');
            $table->unsignedBigInteger('actual_unit_cost')->nullable();
            $table->unsignedBigInteger('line_cost');
            $table->string('status', 20);
            $table->string('unavailable_note')->nullable();
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
