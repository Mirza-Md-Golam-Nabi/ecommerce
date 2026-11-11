<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->restrictOnDelete();
            $table->string('name', 200);
            $table->string('slug', 200)->unique();
            $table->string('search_keywords', 500)->nullable();
            $table->text('description')->nullable();
            $table->string('image')->nullable();
            $table->string('sale_type', 20)->default('fixed');
            $table->string('unit_label', 50);
            $table->unsignedInteger('nominal_weight_grams')->nullable();
            $table->unsignedBigInteger('price');
            $table->unsignedBigInteger('compare_at_price')->nullable();
            $table->unsignedBigInteger('estimated_cost')->default(0);
            $table->unsignedSmallInteger('max_per_order')->nullable();
            $table->boolean('is_available')->default(true);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('total_sold')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['category_id', 'is_active', 'name']);
            $table->index(['is_active', 'total_sold']);
        });

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE products ADD FULLTEXT INDEX products_search_fulltext (name, search_keywords) WITH PARSER ngram');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
