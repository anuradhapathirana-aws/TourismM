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
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;

final class City extends Model implements HasMedia
{
    use HasFactory, HasSlug, InteractsWithImages, SanitizesInput, SoftDeletes;

    protected $fillable = [
        'district_id', 'name', 'slug', 'summary', 'description',
        'latitude', 'longitude', 'is_active', 'sort_order',
        'meta_title', 'meta_description',
    ];

    /** @var list<string> */
    protected array $plainText = ['name', 'summary', 'meta_title', 'meta_description'];

    /** @var list<string> */
    protected array $richText = ['description'];

    protected function casts(): array
    {
        return [
            'latitude' => 'float',
            'longitude' => 'float',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('cover')->singleFile()->acceptsMimeTypes(self::IMAGE_MIMES);
    }

    public function district(): BelongsTo
    {
        return $this->belongsTo(District::class);
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }

    public function publishedDestinations(): HasMany
    {
        return $this->destinations()->where('status', DestinationStatus::Published);
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
