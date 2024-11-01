<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions\Traits;

use Illuminate\Database\Eloquent\Model;
use Spatie\Sluggable\HasSlug as BaseHasSlug;

trait HasSlug
{
    use BaseHasSlug;

    protected static function bootHasSlug(): void
    {
        // Auto generate slugs early before validation
        static::creating(function (Model $model): void {
            if ($model->exists && $model->getSlugOptions()->generateSlugsOnUpdate) {
                $model->generateSlugOnUpdate();
            } elseif (! $model->exists && $model->getSlugOptions()->generateSlugsOnCreate) {
                $model->generateSlugOnCreate();
            }
        });
    }
}
