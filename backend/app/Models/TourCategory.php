<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\SanitizesInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

/**
 * How trips are classified: Beach Tours, Cultural & Heritage Tours, Wildlife & Safari …
 * One category has many tours (7 Days Tour, 10 Days Tour …); tours pick their destination points per day.
 * (Map themes are separate: they only drive the website's explore map.)
 */
final class TourCategory extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithImages, SanitizesInput, SoftDeletes;

    protected $fillable = ['name', 'slug', 'icon', 'summary', 'sort_order', 'is_active'];

    /** @var list<string> */
    protected array $plainText = ['name', 'summary'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile()->acceptsMimeTypes(self::IMAGE_MIMES);
    }

    public function tours(): HasMany
    {
        return $this->hasMany(Tour::class);
    }

    /** Short website label: the name without a trailing "Tours" ("Cultural & Heritage Tours" → "Cultural & Heritage"). */
    public function label(): string
    {
        $label = trim((string) preg_replace('/\s+tours?$/i', '', (string) $this->name));

        return $label !== '' ? $label : (string) $this->name;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
