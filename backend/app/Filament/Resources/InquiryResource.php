<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Enums\InquirySource;
use App\Enums\InquiryStatus;
use App\Filament\Resources\InquiryResource\InquiryActions;
use App\Filament\Resources\InquiryResource\InquiryForm;
use App\Filament\Resources\InquiryResource\InquiryInfolist;
use App\Filament\Resources\InquiryResource\Pages;
use App\Filament\Resources\InquiryResource\RelationManagers\TimelineRelationManager;
use App\Models\Inquiry;
use App\Support\Validation\InquiryRules;
use Filament\Forms\Form;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

/**
 * Tour inquiries inbox ("Build your own journey" requests from the website + requests entered by staff).
 * List → view page (full request + timeline) → change status / assign / reply by WhatsApp or email.
 */
final class InquiryResource extends Resource
{
    protected static ?string $model = Inquiry::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox-arrow-down';

    protected static ?string $navigationGroup = 'Inquiries';

    protected static ?string $navigationLabel = 'Tour inquiries';

    protected static ?string $modelLabel = 'inquiry';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'reference';

    public static function form(Form $form): Form
    {
        return $form->columns(['lg' => 4])->schema(InquiryForm::schema());
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return InquiryInfolist::make($infolist);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query
                ->with(['guest:id,name,email,country,deleted_at', 'assignee:id,name'])
                ->withCount(['destinations', 'activities']))
            ->defaultSort('created_at', 'desc')
            ->recordUrl(fn (Inquiry $record) => self::getUrl('view', ['record' => $record]))
            ->columns([
                Tables\Columns\TextColumn::make('reference')->searchable()->sortable()->weight('medium'),
                Tables\Columns\TextColumn::make('guest.name')->label('Guest')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('guest.email')->label('Email')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('guest.country')->label('Country')->toggleable(),
                Tables\Columns\TextColumn::make('arrival_date')->label('Arrival')->date('d M Y')->sortable(),
                Tables\Columns\TextColumn::make('nights')->label('Nights')
                    ->state(fn (Inquiry $record) => $record->nights()),
                Tables\Columns\TextColumn::make('guests')->label('Guests')
                    ->state(fn (Inquiry $record) => $record->guestCount())
                    ->tooltip(fn (Inquiry $record) => $record->travellersLabel()),
                Tables\Columns\TextColumn::make('picks')->label('Places / acts')->toggleable(isToggledHiddenByDefault: true)
                    ->state(fn (Inquiry $record) => "{$record->destinations_count} / {$record->activities_count}"),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('assignee.name')->label('Assigned')->placeholder('—')->toggleable(),
                Tables\Columns\IconColumn::make('source')->icon(fn (InquirySource $state) => $state->getIcon())
                    ->tooltip(fn (Inquiry $record) => $record->source->getLabel())->color('gray')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->label('Received')->since()->sortable()
                    ->dateTimeTooltip('d M Y, H:i'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(InquiryStatus::class)->multiple(),
                Tables\Filters\SelectFilter::make('assigned_to')->label('Assigned to')
                    ->options(fn () => InquiryRules::assignableUsers()),
                Tables\Filters\Filter::make('mine')->label('Assigned to me')->toggle()
                    ->query(fn (Builder $query) => $query->where('assigned_to', auth()->id())),
                Tables\Filters\Filter::make('arriving_soon')->label('Arriving in the next 30 days')->toggle()
                    ->query(fn (Builder $query) => $query->whereBetween('arrival_date', [today(), today()->addDays(30)])),
                Tables\Filters\SelectFilter::make('source')->options(InquirySource::class),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                InquiryActions::tableStatus(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    Tables\Actions\RestoreBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [TimelineRelationManager::class];
    }

    /** Sidebar badge: inquiries nobody has handled yet. */
    public static function getNavigationBadge(): ?string
    {
        $new = Inquiry::query()->where('status', InquiryStatus::New)->count();

        return $new > 0 ? (string) $new : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'New inquiries';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([SoftDeletingScope::class]);
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['reference', 'guest.name', 'guest.email', 'guest.phone'];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInquiries::route('/'),
            'create' => Pages\CreateInquiry::route('/create'),
            'view' => Pages\ViewInquiry::route('/{record}'),
            'edit' => Pages\EditInquiry::route('/{record}/edit'),
        ];
    }
}
