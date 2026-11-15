<?php

declare(strict_types=1);

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Stevebauman\Purify\Facades\Purify;

/**
 * Last line of defence against stored XSS, applied on every save (admin, seeders, future API):
 *  - $richText columns are purified with the allow-list in config/purify.php ("rich" config)
 *  - $plainText columns have all tags stripped and whitespace trimmed
 *  - $plainTextLists (JSON arrays of strings) get the same per item; empty items are dropped
 *
 * @mixin Model
 */
trait SanitizesInput
{
    public static function bootSanitizesInput(): void
    {
        static::saving(function (Model $model): void {
            foreach ($model->plainTextAttributes() as $attr) {
                $value = $model->getAttribute($attr);
                if (is_string($value)) {
                    $clean = trim(strip_tags($value));
                    $model->setAttribute($attr, $clean === '' ? null : $clean);
                }
            }

            // JSON lists of short texts (highlights, inclusions …): strip tags per item, drop empty items
            foreach ($model->plainTextListAttributes() as $attr) {
                $value = $model->getAttribute($attr);
                if (is_array($value)) {
                    $clean = array_values(array_filter(
                        array_map(fn ($item) => is_string($item) ? trim(strip_tags($item)) : null, $value),
                        fn (?string $item) => $item !== null && $item !== '',
                    ));
                    $model->setAttribute($attr, $clean === [] ? null : $clean);
                }
            }

            foreach ($model->richTextAttributes() as $attr) {
                $value = $model->getAttribute($attr);
                if (is_string($value)) {
                    $clean = trim(Purify::config('rich')->clean($value));
                    $model->setAttribute($attr, $clean === '' ? null : $clean);
                }
            }
        });
    }

    /** @return list<string> */
    public function plainTextAttributes(): array
    {
        return property_exists($this, 'plainText') ? $this->plainText : [];
    }

    /** @return list<string> */
    public function plainTextListAttributes(): array
    {
        return property_exists($this, 'plainTextLists') ? $this->plainTextLists : [];
    }

    /** @return list<string> */
    public function richTextAttributes(): array
    {
        return property_exists($this, 'richText') ? $this->richText : [];
    }
}
