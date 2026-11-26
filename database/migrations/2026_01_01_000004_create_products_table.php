<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Модели товаров. Подкатегория хранится в category_id (родительская категория — через parent_id).
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories');
            $table->string('name');
            $table->string('brand');
            $table->text('description');
            $table->text('composition');
            $table->decimal('price', 10, 2);
            $table->string('image');
            $table->timestamps();

            $table->unique(['name', 'brand']);
            $table->index('brand');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
