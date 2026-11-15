<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource\RelationManagers;

use App\Enums\InquiryStatus;
use App\Models\InquiryNote;
use App\Support\Validation\InquiryRules;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** Timeline under an inquiry: staff notes and status changes, newest first. Rows are never edited or deleted. */
final class TimelineRelationManager extends RelationManager
{
    protected static string $relationship = 'timeline';

    protected static ?string $title = 'Timeline';

    protected static ?string $icon = 'heroicon-m-clock';

    /** Notes can be added from the read-only view page too. */
    public function isReadOnly(): bool
    {
        return false;
    }

    public function form(Form $form): Form
    {
        return $form->schema([
            Textarea::make('body')->label('Note')->required()->rows(4)->maxLength(InquiryRules::NOTE_MAX)
                ->placeholder('e.g. Called the guest. They prefer a beach stay at the end of the trip.')
                ->rules(InquiryRules::statusChange()['note'])
                ->columnSpanFull(),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('user:id,name'))
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50])
            ->columns([
                Tables\Columns\TextColumn::make('created_at')->label('When')->since()->dateTimeTooltip('d M Y, H:i'),
                Tables\Columns\TextColumn::make('user.name')->label('By')->placeholder('System'),
                Tables\Columns\TextColumn::make('change')->label('Status change')->badge()
                    ->state(fn (InquiryNote $record) => $record->status_to
                        ? ($record->status_from?->getLabel() ?? '—').' → '.$record->status_to->getLabel()
                        : null)
                    ->color(fn (InquiryNote $record) => $record->status_to instanceof InquiryStatus ? $record->status_to->getColor() : 'gray')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('body')->label('Note')->wrap()->placeholder('—'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()->label('Add note')->icon('heroicon-m-pencil-square')
                    ->modalHeading('Add a note')->modalWidth('lg')
                    ->mutateFormDataUsing(fn (array $data) => [...$data, 'user_id' => auth()->id()])
                    ->createAnother(false),
            ])
            ->emptyStateHeading('No notes yet')
            ->emptyStateDescription('Status changes and staff notes appear here.');
    }
}
