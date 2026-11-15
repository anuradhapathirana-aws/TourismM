<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\SanitizesInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

/**
 * Library of things guests can do (Jeep safari, White-water rafting, Tea factory tour …).
 * Created once in the admin, then picked per itinerary day when planning a tour.
 */
final class Activity extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithImages, SanitizesInput, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'sort_order', 'is_active'];

    /** @var list<string> */
    protected array $plainText = ['name', 'description'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    /** "Related images": first one is the activity's main photo. */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('images')->acceptsMimeTypes(self::IMAGE_MIMES);
    }

    /** Itinerary days that include this activity. */
    public function tourDays(): BelongsToMany
    {
        return $this->belongsToMany(TourDay::class)->withPivot('sort_order');
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
