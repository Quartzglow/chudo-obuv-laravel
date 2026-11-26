<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Заказ: дата заказа, ФИО клиента, итоговая сумма (в рублях).
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_name');
            $table->date('ordered_at');
            $table->decimal('total', 12, 2)->default(0);
            // Признак того, что количество товарных позиций уже списано со склада.
            $table->boolean('stock_applied')->default(false);
            $table->timestamps();

            $table->index('ordered_at');
            $table->index('customer_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
