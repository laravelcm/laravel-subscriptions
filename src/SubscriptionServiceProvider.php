<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions;

use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

final class SubscriptionServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package->name('laravel-subscriptions')
            ->hasConfigFile('laravel-subscriptions')
            ->hasMigrations([
                'create_plans_table',
                'create_plan_features_table',
                'create_plan_subscriptions_table',
                'create_plan_subscription_usage_table',
            ])
            ->hasInstallCommand(function (InstallCommand $command): void {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToStarRepoOnGitHub('laravelcm/laravel-subscriptions');
            });
    }
}
