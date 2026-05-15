<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('laravel-subscriptions.tables.subscription_usage'), function (Blueprint $table): void {
            $table->unsignedBigInteger('used')->change();
        });
    }

    public function down(): void
    {
        Schema::table(config('laravel-subscriptions.tables.subscription_usage'), function (Blueprint $table): void {
            $table->unsignedSmallInteger('used')->change();
        });
    }
};
