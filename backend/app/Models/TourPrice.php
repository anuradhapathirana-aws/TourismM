<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\SanitizesInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row of a tour's group-size price table: "3–4 travellers · Van · USD 540 per person". */
final class TourPrice extends Model
{
    use SanitizesInput;

    protected $fillable = ['tour_id', 'pax_from', 'pax_to', 'vehicle', 'price_per_person'];

    /** @var list<string> */
    protected array $plainText = ['vehicle'];

    protected $touches = ['tour'];

    protected function casts(): array
    {
        return ['pax_from' => 'integer', 'pax_to' => 'integer', 'price_per_person' => 'decimal:2'];
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /** "1", "3–4", "7+" */
    public function paxLabel(): string
    {
        return match (true) {
            $this->pax_to === null => "{$this->pax_from}+",
            $this->pax_to === $this->pax_from => (string) $this->pax_from,
            default => "{$this->pax_from}–{$this->pax_to}",
        };
    }
}
