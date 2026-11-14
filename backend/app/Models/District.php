<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class District extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = ['province_id', 'name', 'slug'];

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }

    public function cities(): HasMany
    {
        return $this->hasMany(City::class);
    }
}
