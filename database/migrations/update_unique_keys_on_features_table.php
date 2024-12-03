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
            $table->dropUnique(config('laravel-subscriptions.tables.features') . '_slug_unique');
            $table->unique(['plan_id', 'slug']);
        });
    }
};
