<?php

declare(strict_types=1);

namespace Laravelcm\Subscriptions\Traits;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Spatie\Translatable\Events\TranslationHasBeenSetEvent;
use Spatie\Translatable\Exceptions\AttributeIsNotTranslatable;
use Spatie\Translatable\HasTranslations as BaseHasTranslations;

trait HasTranslations
{
    use BaseHasTranslations;

    public function getAttributeValue(mixed $key): mixed
    {
        if (! $this->isTranslatableAttribute($key)) {
            return parent::getAttributeValue($key);
        }

        return $this->getTranslation($key, config('app.locale')) ?: Arr::first($this->getTranslations($key));
    }

    /**
     * @throws AttributeIsNotTranslatable
     */
    public function getTranslations(?string $key = null): array
    {
        if ($key !== null) {
            $this->guardAgainstNonTranslatableAttribute($key);

            $value = array_filter(
                json_decode($this->getAttributes()[$key] ?? '' ?: '{}', true) ?: [],
                fn ($value) => $value !== null && $value !== ''
            );

            // Inject default translation if none supplied
            if (! is_array($value)) {
                $oldValue = $value;

                if ($this->hasSetMutator($key)) {
                    $method = 'set' . Str::studly($key) . 'Attribute';
                    $value = $this->{$method}($value);
                }

                $value = [$locale = app()->getLocale() => $value];

                $this->attributes[$key] = $this->asJson($value);
                event(new TranslationHasBeenSetEvent($this, $key, $locale, $oldValue, $value));
            }

            return $value;
        }

        return array_reduce($this->getTranslatableAttributes(), function ($result, $item) {
            $result[$item] = $this->getTranslations($item);

            return $result;
        });
    }

    public function attributesToArray(): array
    {
        $values = array_map(fn ($attribute) => $this->getTranslation($attribute, config('app.locale')) ?: null, $keys = $this->getTranslatableAttributes());

        return array_replace(parent::attributesToArray(), array_combine($keys, $values));
    }

    public function mergeTranslatable(array $translatable): void
    {
        $this->translatable = array_merge($this->translatable, $translatable);
    }
}
