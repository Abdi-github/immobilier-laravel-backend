<?php

declare(strict_types=1);

namespace App\Domain\Shared\Traits;

use Illuminate\Support\Str;

trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = static::generateUniqueSlug($model);
            }
        });
    }

    protected static function generateUniqueSlug($model): string
    {
        $source = $model->getSlugSource();
        $slug = Str::slug($source);
        $originalSlug = $slug;
        $counter = 1;

        while (static::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    protected function getSlugSource(): string
    {
        $field = $this->slugSourceField ?? 'name';
        $value = $this->{$field};

        if (is_array($value)) {
            return $value['en'] ?? $value[array_key_first($value)] ?? '';
        }

        return (string) $value;
    }
}
