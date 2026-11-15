<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\SanitizesInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\MediaLibrary\HasMedia;

/** One itinerary day: the points visited (in order), where the guests sleep, meals, driving, activities. */
final class TourDay extends Model implements HasMedia
{
    use InteractsWithImages, SanitizesInput;

    protected $fillable = [
        'tour_id', 'day_number', 'title', 'description', 'overnight_city_id', 'hotel',
        'meals', 'distance_km', 'drive_hours',
    ];

    /** @var list<string> */
    protected array $plainText = ['title', 'description', 'hotel'];

    protected $touches = ['tour'];

    protected function casts(): array
    {
        return [
            'day_number' => 'integer',
            'meals' => 'array',
            'distance_km' => 'integer',
            'drive_hours' => 'float',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile()->acceptsMimeTypes(self::IMAGE_MIMES);
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function overnightCity(): BelongsTo
    {
        return $this->belongsTo(City::class, 'overnight_city_id');
    }

    /** Activities from the library (Activity), in the order the admin picked them. */
    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /** Destination points visited this day, in visit order. */
    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(Destination::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }
}
