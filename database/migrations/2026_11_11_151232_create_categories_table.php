<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('categories')->restrictOnDelete();
            $table->string('name', 100);
            $table->string('slug', 120)->unique();
            $table->string('image')->nullable();
            $table->string('icon', 20)->nullable();
            $table->unsignedTinyInteger('depth')->default(0);
            $table->string('path')->default('')->index();
            $table->boolean('is_leaf')->default(true);
            $table->boolean('is_active')->default(true);
            $table->boolean('show_on_home')->default(false);
            $table->unsignedSmallInteger('home_sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['parent_id', 'name']);
            $table->index(['show_on_home', 'home_sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
