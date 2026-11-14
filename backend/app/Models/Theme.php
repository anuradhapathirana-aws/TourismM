<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ThemeSide;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\SanitizesInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

/** A card on the home-page explore map (Popular Beaches, Wildlife & Nature …). */
final class Theme extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithImages, SanitizesInput, SoftDeletes;

    protected $fillable = ['title', 'slug', 'side', 'sort_order', 'is_active'];

    /** @var list<string> */
    protected array $plainText = ['title'];

    protected function casts(): array
    {
        return [
            'side' => ThemeSide::class,
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function slugSource(): string
    {
        return 'title';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile()->acceptsMimeTypes(self::IMAGE_MIMES);
    }

    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(Destination::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }
}
