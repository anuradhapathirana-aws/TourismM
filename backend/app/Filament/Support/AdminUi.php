<?php

declare(strict_types=1);

namespace App\Filament\Support;

use Closure;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Actions as TableActions;
use Filament\Tables\Columns\ImageColumn;

/**
 * Compact admin UI conventions, applied to every resource (see CLAUDE.md → "Admin UI").
 *
 *  - Forms: 12-column grid (6 on tablets). Size each field to its data with ->span('xs'|'sm'|'md'|'lg'|'xl'|'full').
 *  - Help text: ->tip('…') shows a "?" icon after the label; the text appears on hover. Don't use ->helperText().
 *  - Validation: browser checks while typing (public/js/admin-validation.js, driven by the field's HTML attributes:
 *    required, minLength, maxLength, minValue, maxValue, integer, email, ->livePattern()), full server rules on submit.
 *  - Sections are compact. Table row actions are icon-only buttons with their label as a tooltip.
 */
final class AdminUi
{
    /** Columns per breakpoint for every form grid. */
    public const GRID = ['default' => 1, 'md' => 6, 'xl' => 12];

    /**
     * Field widths, matched to the length of the data the field holds.
     *
     * @var array<string, array<string, int|string>>
     */
    public const SPANS = [
        'xs' => ['default' => 'full', 'md' => 2, 'xl' => 2],   // numbers, sort order, toggles, minutes
        'sm' => ['default' => 'full', 'md' => 3, 'xl' => 3],   // coordinates, short selects, dates, phone
        'md' => ['default' => 'full', 'md' => 3, 'xl' => 4],   // names, slugs, emails, selects
        'lg' => ['default' => 'full', 'md' => 6, 'xl' => 6],   // addresses, titles, short textareas
        'xl' => ['default' => 'full', 'md' => 6, 'xl' => 8],   // long single-line text
        'full' => ['default' => 'full'],                       // rich text, galleries, long textareas
    ];

    /** Row actions that always render as icon buttons. Custom row actions call ->iconButton() themselves. */
    private const ICON_ROW_ACTIONS = [
        TableActions\EditAction::class,
        TableActions\ViewAction::class,
        TableActions\DeleteAction::class,
        TableActions\RestoreAction::class,
        TableActions\ForceDeleteAction::class,
        TableActions\ReplicateAction::class,
    ];

    /** A form grid using the shared column layout. */
    public static function grid(array $schema): Grid
    {
        return Grid::make(self::GRID)->schema($schema);
    }

    /** '/^[a-z]+$/' → '^[a-z]+$' (delimiters and flags removed) for the browser's RegExp. */
    public static function jsPattern(string $phpRegex): string
    {
        return preg_match('/^(.)(.*)\1[a-zA-Z]*$/s', $phpRegex, $m) ? $m[2] : $phpRegex;
    }

    public static function configure(): void
    {
        Component::macro('span', function (string $size): Component {
            /** @var Component $this */
            return $this->columnSpan(AdminUi::SPANS[$size] ?? AdminUi::SPANS['full']);
        });

        Field::macro('tip', function (string|Closure|null $text): Field {
            /** @var Field $this */
            return $this->hintIcon('heroicon-m-question-mark-circle', $text)->hintColor('gray');
        });

        // Instant browser check for a regex rule (public/js/admin-validation.js). Keep the server ->regex()/rules too.
        TextInput::macro('livePattern', function (string $phpRegex, string $message): TextInput {
            /** @var TextInput $this */
            return $this->extraInputAttributes([
                'data-lg-pattern' => AdminUi::jsPattern($phpRegex),
                'data-lg-pattern-message' => $message,
            ], merge: true);
        });

        Section::configureUsing(fn (Section $section) => $section->compact());

        foreach (self::ICON_ROW_ACTIONS as $class) {
            $class::configureUsing(
                fn (TableActions\Action $action) => $action
                    ->iconButton()
                    ->tooltip(fn (): ?string => $action->getLabel()),
                isImportant: true,
            );
        }

        ImageColumn::configureUsing(fn (ImageColumn $column) => $column->size(32));
    }
}
