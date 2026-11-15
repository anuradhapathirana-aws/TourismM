<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Enums\InquiryType;
use App\Enums\TourPace;
use App\Models\Concerns\SanitizesInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A guest's request for a tour. Custom tours today ("Build your own journey"); package inquiries later (tour_id).
 *
 * Side effects (one place, so the form, quick actions and the API behave the same):
 *  - created: gets a human reference, e.g. LG-2611-00042
 *  - status → booked/lost sets closed_at; back to an open status clears it
 *  - every status change adds a timeline row (who, from → to, optional note in $statusNote)
 */
final class Inquiry extends Model
{
    use HasFactory, SanitizesInput, SoftDeletes;

    protected $fillable = [
        'guest_id', 'type', 'source', 'status', 'assigned_to', 'tour_id',
        'arrival_date', 'arrival_time', 'departure_date', 'flexible_dates', 'arrival_airport', 'occasion', 'pace',
        'men', 'women', 'children', 'child_ages',
        'accommodation', 'rooms', 'meal_plan', 'vehicle', 'guide_language', 'budget', 'dietary', 'accessibility',
        'notes', 'referral', 'quoted_amount', 'ip_address', 'user_agent',
    ];

    /** @var list<string> */
    protected array $plainText = ['dietary', 'accessibility', 'notes', 'referral', 'guide_language', 'user_agent'];

    /** Optional note saved with the next status change (set it, then update the status). Not a column. */
    public ?string $statusNote = null;

    protected $attributes = ['type' => 'custom_tour', 'source' => 'website', 'status' => 'new'];

    protected function casts(): array
    {
        return [
            'type' => InquiryType::class,
            'source' => InquirySource::class,
            'status' => InquiryStatus::class,
            'pace' => TourPace::class,
            'arrival_date' => 'date',
            'departure_date' => 'date',
            'flexible_dates' => 'boolean',
            'men' => 'integer',
            'women' => 'integer',
            'children' => 'integer',
            'rooms' => 'integer',
            'child_ages' => 'array',
            'quoted_amount' => 'decimal:2',
            'closed_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        self::created(function (Inquiry $inquiry): void {
            $inquiry->forceFill(['reference' => sprintf('LG-%s-%05d', $inquiry->created_at->format('ym'), $inquiry->id)])->saveQuietly();
        });

        self::saving(function (Inquiry $inquiry): void {
            if ($inquiry->isDirty('status')) {
                $inquiry->closed_at = in_array($inquiry->status, InquiryStatus::open(), true) ? null : ($inquiry->closed_at ?? now());
            }
        });

        self::updated(function (Inquiry $inquiry): void {
            if ($inquiry->wasChanged('status')) {
                $inquiry->timeline()->create([
                    'user_id' => auth()->id(),
                    'status_from' => $inquiry->getRawOriginal('status'),
                    'status_to' => $inquiry->status,
                    'body' => $inquiry->statusNote,
                ]);
                $inquiry->statusNote = null;
            }
        });
    }

    // ---- relations ----

    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class)->withTrashed();
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    /** Destination points the guest would love to visit, in the order they picked them. */
    public function destinations(): BelongsToMany
    {
        return $this->belongsToMany(Destination::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    public function activities(): BelongsToMany
    {
        return $this->belongsToMany(Activity::class)->withPivot('sort_order')->orderByPivot('sort_order');
    }

    /** Staff notes and status changes, newest first. */
    public function timeline(): HasMany
    {
        return $this->hasMany(InquiryNote::class)->latest('id');
    }

    // ---- scopes ----

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', InquiryStatus::open());
    }

    // ---- derived values ----

    public function nights(): ?int
    {
        return $this->arrival_date && $this->departure_date ? (int) $this->arrival_date->diffInDays($this->departure_date) : null;
    }

    public function adults(): int
    {
        return (int) $this->men + (int) $this->women;
    }

    public function guestCount(): int
    {
        return $this->adults() + (int) $this->children;
    }

    /** "2 men, 1 woman, 2 children (6, under 1)" */
    public function travellersLabel(): string
    {
        $part = fn (int $n, string $one, string $many) => $n ? "{$n} ".($n === 1 ? $one : $many) : null;
        $ages = collect($this->child_ages ?? [])->map(fn ($a) => (int) $a === 0 ? 'under 1' : (string) $a)->implode(', ');

        return collect([
            $part((int) $this->men, 'man', 'men'),
            $part((int) $this->women, 'woman', 'women'),
            $this->children ? $part((int) $this->children, 'child', 'children').($ages !== '' ? " ({$ages})" : '') : null,
        ])->filter()->implode(', ') ?: '—';
    }

    /** Label of a config('inquiries.<list>') option, e.g. optionLabel('airports', 'cmb'). Unknown keys come back unchanged. */
    public static function optionLabel(string $list, ?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        $options = (array) config("inquiries.{$list}", []);

        return array_is_list($options) ? $value : ($options[$value] ?? $value);
    }

    public function budgetLabel(): ?string
    {
        $label = self::optionLabel('budgets', $this->budget);

        return $label ? config('tours.currency').' '.$label.' pp' : null;
    }
}
