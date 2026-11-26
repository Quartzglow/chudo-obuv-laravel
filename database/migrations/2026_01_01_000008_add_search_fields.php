<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Поисковые поля в нижнем регистре: SQLite LIKE нечувствителен к регистру
     * только для латиницы, поэтому для кириллицы поиск идёт по нормализованным
     * значениям (нижний регистр), а не по исходным.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->text('search_text')->nullable()->after('image');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('customer_search')->nullable()->after('customer_name');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('search_text');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('customer_search');
        });
    }
};
