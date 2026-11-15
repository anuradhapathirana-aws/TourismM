<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InquiryStatus;
use App\Models\Concerns\SanitizesInput;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** One row of an inquiry's timeline: a staff note, a status change, or both. Written once, never edited. */
final class InquiryNote extends Model
{
    use SanitizesInput;

    protected $fillable = ['inquiry_id', 'user_id', 'status_from', 'status_to', 'body'];

    /** @var list<string> */
    protected array $plainText = ['body'];

    protected function casts(): array
    {
        return ['status_from' => InquiryStatus::class, 'status_to' => InquiryStatus::class];
    }

    public function inquiry(): BelongsTo
    {
        return $this->belongsTo(Inquiry::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
