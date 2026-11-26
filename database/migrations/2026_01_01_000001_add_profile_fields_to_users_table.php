<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавляет к таблице пользователей поля, определённые в предметной области:
     * логин, фамилия, имя, отчество, телефон, роль.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('surname')->nullable()->after('id');
            $table->string('patronymic')->nullable()->after('name');
            $table->string('login')->nullable()->unique()->after('patronymic');
            $table->string('phone')->nullable()->unique()->after('login');
            $table->string('role')->default('user')->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['login']);
            $table->dropUnique(['phone']);
            $table->dropColumn(['surname', 'patronymic', 'login', 'phone', 'role']);
        });
    }
};
