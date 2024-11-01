<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(config('laravel-subscriptions.tables.subscriptions'), function (Blueprint $table): void {
            $table->dropUnique(config('laravel-subscriptions.tables.subscriptions') . '_slug_unique');
        });
    }
};
