<?php

declare(strict_types=1);

namespace App\Filament\Resources\InquiryResource\Pages;

use App\Enums\InquiryStatus;
use App\Filament\Resources\InquiryResource;
use App\Models\Inquiry;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

/** Inbox tabs: New first (what needs a reply), then Open (being worked on), Booked, Lost, All. */
final class ListInquiries extends ListRecords
{
    protected static string $resource = InquiryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()->label('New inquiry')->icon('heroicon-m-plus'),
        ];
    }

    public function getTabs(): array
    {
        $counts = Inquiry::query()->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $count = fn (InquiryStatus ...$statuses) => collect($statuses)->sum(fn (InquiryStatus $s) => (int) ($counts[$s->value] ?? 0)) ?: null;

        return [
            'new' => Tab::make('New')->icon('heroicon-m-sparkles')
                ->badge($count(InquiryStatus::New))->badgeColor('info')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', InquiryStatus::New)),
            'open' => Tab::make('Open')
                ->badge($count(...InquiryStatus::open()))
                ->modifyQueryUsing(fn (Builder $query) => $query->whereIn('status', InquiryStatus::open())),
            'booked' => Tab::make('Booked')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', InquiryStatus::Booked)),
            'lost' => Tab::make('Lost')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('status', InquiryStatus::Lost)),
            'all' => Tab::make('All'),
        ];
    }

    public function getDefaultActiveTab(): string|int|null
    {
        return 'open';
    }
}
