<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Derived data (docs/04-reporting.md) — rebuildable with `reports:rebuild`.
     * Placement metrics use placed_at; revenue/profit metrics use delivered_at. Money is poisha.
     */
    public function up(): void
    {
        Schema::create('daily_sales_summaries', function (Blueprint $table) {
            $table->date('date')->primary();
            $table->unsignedInteger('orders_placed')->default(0);
            $table->unsignedInteger('orders_cancelled')->default(0);
            $table->unsignedInteger('orders_delivered')->default(0);
            $table->unsignedBigInteger('booked_sales')->default(0);
            $table->unsignedBigInteger('revenue')->default(0);
            $table->unsignedBigInteger('delivery_income')->default(0);
            $table->unsignedBigInteger('cogs')->default(0);
            $table->bigInteger('gross_profit')->default(0);
            $table->unsignedInteger('estimated_cost_orders')->default(0);
            $table->unsignedBigInteger('lost_sales')->default(0);
            $table->unsignedInteger('unavailable_lines')->default(0);
            $table->unsignedInteger('items_delivered')->default(0);
            $table->unsignedInteger('new_customers')->default(0);
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_sales_summaries');
    }
};
