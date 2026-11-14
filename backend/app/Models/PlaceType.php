<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\SanitizesInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Kind of destination point: Waterfall, Temple, Beach, National Park … */
final class PlaceType extends Model
{
    use HasFactory, HasSlug, SanitizesInput;

    protected $fillable = ['name', 'slug', 'icon', 'sort_order', 'is_active'];

    /** @var list<string> */
    protected array $plainText = ['name'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'sort_order' => 'integer'];
    }

    public function destinations(): HasMany
    {
        return $this->hasMany(Destination::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
}
