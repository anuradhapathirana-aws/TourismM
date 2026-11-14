<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

final class Province extends Model
{
    use HasFactory, HasSlug;

    protected $fillable = ['name', 'slug'];

    public function districts(): HasMany
    {
        return $this->hasMany(District::class);
    }

    public function cities(): HasManyThrough
    {
        return $this->hasManyThrough(City::class, District::class);
    }
}
