<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations. Derived per day × product: sales from orders delivered that day,
     * unavailable/lost figures from orders placed that day.
     */
    public function up(): void
    {
        Schema::create('daily_product_sales', function (Blueprint $table) {
            $table->date('date');
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->unsignedBigInteger('grams_sold')->default(0);
            $table->unsignedBigInteger('revenue')->default(0);
            $table->unsignedBigInteger('cogs')->default(0);
            $table->bigInteger('profit')->default(0);
            $table->unsignedInteger('unavailable_count')->default(0);
            $table->unsignedBigInteger('lost_value')->default(0);

            $table->primary(['date', 'product_id']);
            $table->index(['product_id', 'date']);
            $table->index(['category_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_product_sales');
    }
};
