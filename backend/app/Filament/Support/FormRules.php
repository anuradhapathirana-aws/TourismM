<?php

declare(strict_types=1);

namespace App\Filament\Support;

use App\Support\Validation\CommonRules;
use Closure;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Bridges App\Support\Validation\*Rules into Filament fields, so the admin form and any other
 * entry point validate with exactly the same rule set (single source of truth).
 */
final class FormRules
{
    /**
     * @param  Closure(?Model, Get): array<string, list<mixed>>  $ruleSet
     */
    public static function for(Closure $ruleSet, string $key): Closure
    {
        return fn (Get $get, ?Model $record): array => $ruleSet($record, $get)[$key] ?? [];
    }

    /** Name field that fills the slug while the slug hasn't been edited by hand. */
    public static function sluggedName(TextInput $input, string $slugField = 'slug'): TextInput
    {
        return $input
            ->live(onBlur: true)
            ->afterStateUpdated(function (Get $get, Set $set, ?string $old, ?string $state) use ($slugField): void {
                if (blank($get($slugField)) || $get($slugField) === Str::slug((string) $old)) {
                    $set($slugField, Str::slug((string) $state));
                }
            });
    }

    public static function slugInput(Closure $ruleSet, int $max): TextInput
    {
        return TextInput::make('slug')
            ->required()
            ->maxLength($max)
            ->regex(CommonRules::SLUG_REGEX)
            ->livePattern(CommonRules::SLUG_REGEX, 'Use lowercase letters, numbers and single hyphens only (e.g. ravana-falls).')
            ->tip('URL name: lowercase letters, numbers and hyphens. Filled from the name automatically.')
            ->dehydrateStateUsing(fn (?string $state) => Str::slug((string) $state))
            ->rules(self::for($ruleSet, 'slug'));
    }
}
