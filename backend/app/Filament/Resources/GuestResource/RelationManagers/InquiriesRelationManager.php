<?php

declare(strict_types=1);

namespace App\Filament\Resources\GuestResource\RelationManagers;

use App\Filament\Resources\InquiryResource;
use App\Models\Inquiry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/** The guest's inquiries (newest first). Opening one goes to the inquiry page. */
final class InquiriesRelationManager extends RelationManager
{
    protected static string $relationship = 'inquiries';

    protected static ?string $icon = 'heroicon-m-inbox-arrow-down';

    public function isReadOnly(): bool
    {
        return true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('reference')
            ->modifyQueryUsing(fn (Builder $query) => $query->with('assignee:id,name'))
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Inquiry $record) => InquiryResource::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('reference')->weight('medium'),
                Tables\Columns\TextColumn::make('arrival_date')->label('Arrival')->date('d M Y'),
                Tables\Columns\TextColumn::make('nights')->state(fn (Inquiry $record) => $record->nights()),
                Tables\Columns\TextColumn::make('guests')->state(fn (Inquiry $record) => $record->guestCount()),
                Tables\Columns\TextColumn::make('status')->badge(),
                Tables\Columns\TextColumn::make('assignee.name')->label('Assigned')->placeholder('—'),
                Tables\Columns\TextColumn::make('created_at')->label('Received')->since(),
            ])
            ->actions([
                Tables\Actions\Action::make('open')->icon('heroicon-o-eye')->iconButton()->tooltip('Open inquiry')
                    ->url(fn (Inquiry $record) => InquiryResource::getUrl('view', ['record' => $record])),
            ]);
    }
}
