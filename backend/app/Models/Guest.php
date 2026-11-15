<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Concerns\SanitizesInput;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A person who contacted us. Stored once and reused by all their inquiries (and later bookings, reviews).
 * Returning guests are recognised by email (stored lower-case, unique).
 */
final class Guest extends Model
{
    use HasFactory, SanitizesInput, SoftDeletes;

    protected $fillable = ['name', 'email', 'phone', 'country', 'preferred_contact', 'notes'];

    /** @var list<string> */
    protected array $plainText = ['name', 'phone', 'country', 'notes'];

    /** Normalised so "Anna@Mail.com " and "anna@mail.com" are the same guest. */
    protected function email(): Attribute
    {
        return Attribute::set(fn (?string $value) => $value === null ? null : mb_strtolower(trim($value)));
    }

    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    /** https://wa.me/… link for the guest's phone (null when there is no usable number). */
    public function whatsappUrl(): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $this->phone);

        return strlen((string) $digits) >= 7 ? "https://wa.me/{$digits}" : null;
    }

    public function preferredContactLabel(): ?string
    {
        return Inquiry::optionLabel('contact_methods', $this->preferred_contact);
    }
}
