<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('laravel-subscriptions.tables.features'), function (Blueprint $table): void {
            $table->string('unit')->nullable()->after('sort_order');
            $table->string('display_unit')->nullable()->after('unit');
        });
    }

    public function down(): void
    {
        Schema::table(config('laravel-subscriptions.tables.features'), function (Blueprint $table): void {
            $table->dropColumn([
                'unit',
                'display_unit',
            ]);
        });
    }
};
