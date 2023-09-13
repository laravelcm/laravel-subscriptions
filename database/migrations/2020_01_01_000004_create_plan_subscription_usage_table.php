<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Laravelcm\Subscriptions\Models\Feature;
use Laravelcm\Subscriptions\Models\Subscription;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create(config('laravel-subscriptions.tables.subscription_usage'), function (Blueprint $table): void {
            $table->id();

            $table->foreignIdFor(Subscription::class);
            $table->foreignIdFor(Feature::class);
            $table->unsignedSmallInteger('used');
            $table->string('timezone')->nullable();

            $table->dateTime('valid_until')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('laravel-subscriptions.tables.subscription_usage'));
    }
};
