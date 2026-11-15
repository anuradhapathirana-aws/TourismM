<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\DestinationStatus;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\SanitizesInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

/** A destination point on the map, inside a city (e.g. Ravana Falls in Ella). */
final class Destination extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithImages, SanitizesInput, SoftDeletes;

    public const MAX_GALLERY = 12;

    protected $fillable = [
        'city_id', 'place_type_id', 'name', 'slug', 'summary', 'description',
        'latitude', 'longitude', 'address', 'opening_hours', 'entry_fee',
        'best_time_to_visit', 'visit_duration_minutes', 'status', 'is_featured',
        'sort_order', 'meta_title', 'meta_description',
    ];

    /** @var list<string> */
    protected array $plainText = [
        'name', 'summary', 'address', 'opening_hours', 'entry_fee',
        'best_time_to_visit', 'meta_title', 'meta_description',
    ];

    /** @var list<string> */
    protected array $richText = ['description'];

    protected function casts(): array
    {
        return [
            'status' => DestinationStatus::class,
            'latitude' => 'float',
            'longitude' => 'float',
            'is_featured' => 'boolean',
            'visit_duration_minutes' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile()->acceptsMimeTypes(self::IMAGE_MIMES);
        $this->addMediaCollection('gallery')->acceptsMimeTypes(self::IMAGE_MIMES);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function placeType(): BelongsTo
    {
        return $this->belongsTo(PlaceType::class);
    }

    public function themes(): BelongsToMany
    {
        return $this->belongsToMany(Theme::class);
    }

    /** Itinerary days that visit this point (→ "tours that visit here"). */
    public function tourDays(): BelongsToMany
    {
        return $this->belongsToMany(TourDay::class)->withPivot('sort_order');
    }

    /** Published, inside an active city: what the public website may see. */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', DestinationStatus::Published)
            ->whereHas('city', fn (Builder $q) => $q->where('is_active', true));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
