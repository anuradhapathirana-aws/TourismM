<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TourPace;
use App\Enums\TourStatus;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\InteractsWithImages;
use App\Models\Concerns\SanitizesInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\HasMedia;

/**
 * A sellable trip ("7 Days Cultural Triangle"). Belongs to exactly one TourCategory.
 * Planned day by day (TourDay → destination points); priced by group size (TourPrice).
 */
final class Tour extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithImages, SanitizesInput, SoftDeletes;

    protected $fillable = [
        'tour_category_id', 'title', 'slug', 'summary', 'description', 'highlights', 'inclusions', 'exclusions',
        'pace', 'group_min', 'group_max', 'start_point', 'end_point', 'best_months',
        'status', 'is_featured', 'sort_order', 'meta_title', 'meta_description',
    ];

    /** @var list<string> */
    protected array $plainText = ['title', 'summary', 'start_point', 'end_point', 'meta_title', 'meta_description'];

    /** @var list<string> */
    protected array $plainTextLists = ['highlights', 'inclusions', 'exclusions'];

    /** @var list<string> */
    protected array $richText = ['description'];

    protected function casts(): array
    {
        return [
            'highlights' => 'array',
            'inclusions' => 'array',
            'exclusions' => 'array',
            'best_months' => 'array',
            'pace' => TourPace::class,
            'status' => TourStatus::class,
            'is_featured' => 'boolean',
            'group_min' => 'integer',
            'group_max' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    public function slugSource(): string
    {
        return 'title';
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile()->acceptsMimeTypes(self::IMAGE_MIMES);
        $this->addMediaCollection('gallery')->acceptsMimeTypes(self::IMAGE_MIMES);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(TourCategory::class, 'tour_category_id');
    }

    public function days(): HasMany
    {
        return $this->hasMany(TourDay::class)->orderBy('day_number');
    }

    public function prices(): HasMany
    {
        return $this->hasMany(TourPrice::class)->orderBy('pax_from');
    }

    /** Published, in an active (not deleted) category: what the public website may see. */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('status', TourStatus::Published)
            ->whereHas('category', fn (Builder $category) => $category->where('is_active', true));
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('title');
    }

    /** "7 Days / 6 Nights", "1 Day". */
    public static function durationLabel(int $days): string
    {
        if ($days <= 1) {
            return $days === 1 ? '1 Day' : '';
        }

        $nights = $days - 1;

        return "{$days} Days / {$nights} ".($nights === 1 ? 'Night' : 'Nights');
    }

    /**
     * Deep copy as a new draft: days (with their points in order), prices and photos.
     * Used by the admin "Duplicate" action to turn a 7-day tour into a 10-day one quickly.
     */
    public function duplicate(): self
    {
        return DB::transaction(function (): self {
            // Fresh copy of the row: the admin table adds computed columns (days_count …) to $this
            $copy = self::withTrashed()->findOrFail($this->getKey())->replicate(['slug', 'deleted_at']);
            $copy->title = $this->uniqueCopyTitle();
            $copy->status = TourStatus::Draft;
            $copy->is_featured = false;
            $copy->save();

            foreach ($this->days()->with(['destinations', 'activities', 'media'])->get() as $day) {
                $newDay = $day->replicate();
                $newDay->tour_id = $copy->id;
                $newDay->save();
                $newDay->destinations()->sync(
                    $day->destinations->mapWithKeys(fn (Destination $point) => [$point->id => ['sort_order' => $point->pivot->sort_order]])->all()
                );
                $newDay->activities()->sync(
                    $day->activities->mapWithKeys(fn (Activity $activity) => [$activity->id => ['sort_order' => $activity->pivot->sort_order]])->all()
                );
                $day->getMedia('image')->each(fn ($media) => $media->copy($newDay, 'image'));
            }

            foreach ($this->prices as $price) {
                $copy->prices()->create($price->only(['pax_from', 'pax_to', 'vehicle', 'price_per_person']));
            }

            foreach (['cover', 'gallery'] as $collection) {
                $this->getMedia($collection)->each(fn ($media) => $media->copy($copy, $collection));
            }

            return $copy;
        });
    }

    private function uniqueCopyTitle(): string
    {
        $base = mb_substr($this->title, 0, 140).' (copy)';
        $title = $base;
        $i = 2;
        while (self::withTrashed()->where('title', $title)->exists()) {
            $title = mb_substr($this->title, 0, 136)." (copy {$i})";
            $i++;
        }

        return $title;
    }
}
