<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Fills `slug` from the model's source column when empty and keeps it unique
 * (appends -2, -3 …). Models may override slugSource().
 *
 * @mixin Model
 */
trait HasSlug
{
    public static function bootHasSlug(): void
    {
        static::saving(function (Model $model): void {
            /** @var Model&self $model */
            $slug = Str::slug((string) ($model->slug ?: $model->getAttribute($model->slugSource())));
            $model->slug = $model->uniqueSlug($slug !== '' ? $slug : Str::lower(Str::random(8)));
        });
    }

    public function slugSource(): string
    {
        return 'name';
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    protected function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 2;

        $query = fn (string $s) => static::query()
            ->when(method_exists($this, 'bootSoftDeletes'), fn ($q) => $q->withTrashed())
            ->where('slug', $s)
            ->whereKeyNot($this->getKey());

        while ($query($slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        return $slug;
    }
}
