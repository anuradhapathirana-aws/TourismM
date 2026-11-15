<?php

declare(strict_types=1);

namespace App\Filament\Resources\TourResource;

use App\Enums\Meal;
use App\Enums\TourPace;
use App\Enums\TourStatus;
use App\Filament\Support\AdminUi;
use App\Filament\Support\FormRules;
use App\Filament\Support\ImageFields;
use App\Models\Activity;
use App\Models\Destination;
use App\Models\Tour;
use App\Models\TourDay;
use App\Support\Validation\ActivityRules;
use App\Support\Validation\TourRules;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Get;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * "Plan a tour" form. Tabs keep it on one screen:
 *   Overview · Itinerary (day by day: points in visit order, overnight, hotel, meals, driving, activities, photo)
 *   · Pricing & inclusions (group-size price table) · Gallery & SEO.
 * Side panel: publishing, live "at a glance" summary (duration = number of days), cover image.
 */
final class TourForm
{
    public const MONTHS = [1 => 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    public static function schema(): array
    {
        $rules = fn (?Model $record, Get $get): array => TourRules::rules($record instanceof Tour ? $record : null, $get('status'));

        $isPublished = function (Get $get): bool {
            $status = $get('status');

            return ($status instanceof TourStatus ? $status : TourStatus::tryFrom((string) $status)) === TourStatus::Published;
        };

        return [
            Group::make()->columnSpan(['lg' => 3])->schema([
                Tabs::make('Tour')->persistTabInQueryString()->tabs([
                    Tabs\Tab::make('Overview')->icon('heroicon-m-information-circle')->schema([self::overview($rules)]),
                    Tabs\Tab::make('Itinerary')->icon('heroicon-m-map')
                        ->badge(fn (Get $get) => count($get('days') ?? []) ?: null)
                        ->schema([self::itinerary($rules)]),
                    Tabs\Tab::make('Pricing & inclusions')->icon('heroicon-m-currency-dollar')->schema(self::pricing($rules, $isPublished)),
                    Tabs\Tab::make('Gallery & SEO')->icon('heroicon-m-photo')->schema([
                        AdminUi::grid([
                            ImageFields::gallery()->span('full'),
                            TextInput::make('meta_title')->maxLength(70)->tip('Search-result title, up to 70 characters. Uses the tour title when empty.')
                                ->rules(FormRules::for($rules, 'meta_title'))->span('md'),
                            Textarea::make('meta_description')->maxLength(160)->rows(2)->tip('Search-result snippet, up to 160 characters. Uses the summary when empty.')
                                ->rules(FormRules::for($rules, 'meta_description'))->span('xl'),
                        ]),
                    ]),
                ]),
            ]),

            Group::make()->columnSpan(['lg' => 1])->schema([
                Section::make('Publishing')->schema([
                    ToggleButtons::make('status')
                        ->options(TourStatus::class)
                        ->default(TourStatus::Draft)
                        ->inline()
                        ->required()
                        ->live()
                        ->tip('Only published tours appear on the website. Publishing needs a cover image, at least one day and one price row.')
                        ->rules(FormRules::for($rules, 'status')),
                    Toggle::make('is_featured')->label('Featured')->live()
                        ->tip('Shown first on the home page. Needs a cover image.')
                        ->rules(FormRules::for($rules, 'is_featured')),
                    TextInput::make('sort_order')->label('Sort order')->integer()->default(0)->minValue(0)->maxValue(65535)
                        ->tip('Lower numbers come first.')
                        ->rules(FormRules::for($rules, 'sort_order')),
                ]),
                Section::make('At a glance')->schema([
                    Placeholder::make('glance_duration')->label('Duration')
                        ->content(fn (Get $get) => Tour::durationLabel(count($get('days') ?? [])) ?: 'Add itinerary days'),
                    Placeholder::make('glance_price')->label('Price from')
                        ->content(fn (Get $get) => self::priceFrom($get('prices') ?? [])),
                    Placeholder::make('glance_points')->label('Destination points')
                        ->content(fn (Get $get) => (string) self::points($get('days') ?? [])->count()),
                ]),
                Section::make('Cover image')->schema([
                    ImageFields::single('cover', 'Cover')
                        ->required(fn (Get $get) => $isPublished($get) || (bool) $get('is_featured'))
                        ->validationMessages(['required' => 'A cover image is required to publish or feature a tour.']),
                ]),
            ]),
        ];
    }

    private static function overview(\Closure $rules): Grid
    {
        return AdminUi::grid([
            Select::make('tour_category_id')
                ->label('Category')
                ->relationship('category', 'name', fn (Builder $query) => $query->where('is_active', true)->orderBy('sort_order'))
                ->searchable()
                ->preload()
                ->required()
                ->rules(FormRules::for($rules, 'tour_category_id'))
                ->span('md'),
            FormRules::sluggedName(
                TextInput::make('title')->required()->minLength(3)->maxLength(150)
                    ->placeholder('7 Days Cultural Triangle & Hill Country')
                    ->rules(FormRules::for($rules, 'title'))
            )->span('xl'),
            FormRules::slugInput($rules, 170)->span('md'),
            ToggleButtons::make('pace')
                ->options(TourPace::class)
                ->default(TourPace::Moderate)
                ->inline()
                ->required()
                ->tip('Relaxed: short drives, free time · Moderate: full days · Active: hikes, early starts.')
                ->rules(FormRules::for($rules, 'pace'))
                ->span('md'),
            TextInput::make('group_min')->label('Min guests')->integer()->required()->default(1)->minValue(1)->maxValue(TourRules::MAX_PAX)
                ->rules(FormRules::for($rules, 'group_min'))->span('xs'),
            TextInput::make('group_max')->label('Max guests')->integer()->minValue(1)->maxValue(TourRules::MAX_PAX)
                ->tip('Leave empty for no limit.')
                ->rules(FormRules::for($rules, 'group_max'))
                ->validationMessages(['gte' => 'Max guests must be at least the min guests.'])
                ->span('xs'),
            TextInput::make('start_point')->label('Starts at')->placeholder('Colombo Airport (BIA)')->maxLength(120)
                ->rules(FormRules::for($rules, 'start_point'))->span('md'),
            TextInput::make('end_point')->label('Ends at')->placeholder('Colombo Airport (BIA)')->maxLength(120)
                ->rules(FormRules::for($rules, 'end_point'))->span('md'),
            Select::make('best_months')->label('Best months')->multiple()->options(self::MONTHS)
                ->tip('Months with the best weather for this route.')
                ->rules(FormRules::for($rules, 'best_months'))
                ->nestedRecursiveRules(TourRules::rules()['best_months.*'])
                ->span('md'),
            Textarea::make('summary')->required()->minLength(10)->maxLength(300)->rows(2)
                ->tip('One or two sentences (10–300 characters) for the tour card.')
                ->rules(FormRules::for($rules, 'summary'))->span('full'),
            TagsInput::make('highlights')->placeholder('Type a highlight and press Enter')->reorderable()
                ->tip('Up to 12 short highlights (150 characters each), e.g. “Sigiriya Rock at sunrise”. Drag to reorder.')
                ->rules(FormRules::for($rules, 'highlights'))
                ->nestedRecursiveRules(TourRules::rules()['highlights.*'])
                ->span('full'),
            RichEditor::make('description')
                ->label('Overview text')
                ->toolbarButtons(['bold', 'italic', 'h3', 'bulletList', 'orderedList', 'link', 'undo', 'redo'])
                ->maxLength(20000)
                ->tip('Longer introduction on the tour page (optional).')
                ->rules(FormRules::for($rules, 'description'))
                ->span('full'),
        ]);
    }

    private static function itinerary(\Closure $rules): Repeater
    {
        $day = TourRules::dayRules();

        return Repeater::make('days')
            ->hiddenLabel()
            ->relationship('days')
            ->orderColumn('day_number')
            ->required()
            ->minItems(1)
            ->maxItems(TourRules::MAX_DAYS)
            ->defaultItems(1)
            ->collapsible()
            ->cloneable()
            ->addActionLabel('Add day')
            ->itemLabel(fn (array $state): string => filled($state['title'] ?? null) ? $state['title'] : 'New day')
            ->extraAttributes(['class' => 'lg-itinerary'])
            ->rules(FormRules::for($rules, 'days'))
            ->validationMessages(['min' => 'Add at least one itinerary day.', 'required' => 'Add at least one itinerary day.'])
            ->mutateRelationshipDataBeforeFillUsing(fn (array $data): array => [
                ...$data,
                'destination_ids' => DB::table('destination_tour_day')
                    ->where('tour_day_id', $data['id'])->orderBy('sort_order')->pluck('destination_id')->all(),
                'activity_ids' => DB::table('activity_tour_day')
                    ->where('tour_day_id', $data['id'])->orderBy('sort_order')->pluck('activity_id')->all(),
            ])
            ->schema([
                AdminUi::grid([
                    TextInput::make('title')->label('Day title')->required()->minLength(2)->maxLength(150)
                        ->placeholder('Kandy → Nuwara Eliya')->live(onBlur: true)
                        ->rules($day['title'])->span('lg'),
                    Select::make('overnight_city_id')->label('Overnight in')
                        ->relationship('overnightCity', 'name', fn (Builder $query) => $query->orderBy('name'))
                        ->searchable()->preload()
                        ->tip('City where guests sleep tonight. Empty on the departure day.')
                        ->rules($day['overnight_city_id'])->span('sm'),
                    TextInput::make('hotel')->placeholder('Jetwing Lake or similar, 4★')->maxLength(150)
                        ->rules($day['hotel'])->span('sm'),
                    self::pointsSelect($day)->span('xl'),
                    TextInput::make('distance_km')->label('Drive km')->integer()->minValue(0)->maxValue(1000)
                        ->rules($day['distance_km'])->span('xs'),
                    TextInput::make('drive_hours')->label('Drive hours')->numeric()->step(0.5)->minValue(0)->maxValue(24)
                        ->rules($day['drive_hours'])->span('xs'),
                    Textarea::make('description')->label('What happens today')->rows(3)->maxLength(2000)
                        ->rules($day['description'])->span('lg'),
                    ImageFields::single('image', 'Day photo')->span('md'),
                    ToggleButtons::make('meals')->label('Meals included')->options(Meal::class)->multiple()->inline()
                        ->rules($day['meals'])->nestedRecursiveRules($day['meals.*'])->span('lg'),
                    self::activitiesSelect($day)->span('lg'),
                ]),
            ]);
    }

    /** Points visited this day; the order picked = visit order (saved to the pivot's sort_order). */
    private static function pointsSelect(array $day): Select
    {
        $label = fn (Destination $point): string => $point->name.' · '.($point->city?->name ?? '—');

        return Select::make('destination_ids')
            ->label('Destination points (in visit order)')
            ->multiple()
            ->searchable()
            ->getSearchResultsUsing(fn (string $search): array => Destination::query()
                ->with('city:id,name')
                ->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhereHas('city', fn (Builder $city) => $city->where('name', 'like', "%{$search}%")))
                ->orderBy('name')
                ->limit(50)
                ->get()
                ->mapWithKeys(fn (Destination $point) => [$point->id => $label($point)])
                ->all())
            ->getOptionLabelsUsing(fn (array $values): array => Destination::query()
                ->with('city:id,name')
                ->whereIn('id', $values)
                ->get()
                ->mapWithKeys(fn (Destination $point) => [$point->id => $label($point)])
                ->all())
            ->maxItems(TourRules::MAX_POINTS_PER_DAY)
            ->tip('Search by point or city name. Pick them in the order they are visited; draft points are allowed but only published ones show on the website.')
            ->rules($day['destination_ids'])
            ->nestedRecursiveRules($day['destination_ids.*'])
            ->dehydrated(false)
            ->saveRelationshipsUsing(function (TourDay $record, ?array $state): void {
                $record->destinations()->sync(
                    collect($state ?? [])->values()->mapWithKeys(fn ($id, int $i) => [(int) $id => ['sort_order' => $i + 1]])->all()
                );
            });
    }

    /** Activities from the library (Tours → Activities), searchable; "+" adds a new one without leaving the tour. */
    private static function activitiesSelect(array $day): Select
    {
        $options = fn ($query): array => $query->pluck('name', 'id')->all();

        return Select::make('activity_ids')
            ->label('Activities')
            ->multiple()
            ->searchable()
            ->placeholder('Search activities…')
            ->getSearchResultsUsing(fn (string $search): array => $options(
                Activity::query()->active()->where('name', 'like', "%{$search}%")->ordered()->limit(50)
            ))
            ->getOptionLabelsUsing(fn (array $values): array => $options(Activity::withTrashed()->whereIn('id', $values)))
            ->maxItems(TourRules::MAX_ACTIVITIES_PER_DAY)
            ->tip('Type to search the activity library (Tours → Activities). Up to '.TourRules::MAX_ACTIVITIES_PER_DAY.' per day. Use + to add a missing one.')
            ->rules($day['activity_ids'])
            ->nestedRecursiveRules($day['activity_ids.*'])
            ->createOptionForm(fn (): array => [
                TextInput::make('name')->required()->minLength(2)->maxLength(100)
                    ->rules(ActivityRules::rules()['name']),
                Textarea::make('description')->rows(3)->maxLength(ActivityRules::DESCRIPTION_MAX)
                    ->rules(ActivityRules::rules()['description']),
            ])
            ->createOptionUsing(fn (array $data): int => Activity::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ])->getKey())
            ->createOptionAction(fn ($action) => $action
                ->modalHeading('New activity')
                ->visible(fn (): bool => (bool) auth()->user()?->can('create', Activity::class)))
            ->dehydrated(false)
            ->saveRelationshipsUsing(function (TourDay $record, ?array $state): void {
                $record->activities()->sync(
                    collect($state ?? [])->values()->mapWithKeys(fn ($id, int $i) => [(int) $id => ['sort_order' => $i + 1]])->all()
                );
            });
    }

    private static function pricing(\Closure $rules, \Closure $isPublished): array
    {
        $price = TourRules::priceRules();
        $currency = config('tours.currency');

        return [
            Repeater::make('prices')
                ->label('Price per person by group size')
                ->relationship('prices')
                ->reorderable(false)
                ->collapsible(false)
                ->itemLabel(fn (array $state): string => self::priceRowLabel($state))
                ->maxItems(TourRules::MAX_PRICE_ROWS)
                ->minItems(fn (Get $get) => $isPublished($get) ? 1 : 0)
                ->defaultItems(0)
                ->addActionLabel('Add group size')
                ->tip("Price per person in {$currency}. Larger groups usually pay less each. The website shows the lowest as “From …”. Leave “to” empty for “and more”.")
                ->rules(FormRules::for($rules, 'prices'))
                ->validationMessages(['min' => 'Add at least one price row before publishing.', 'required' => 'Add at least one price row before publishing.'])
                ->schema([
                    AdminUi::grid([
                        TextInput::make('pax_from')->label('From (guests)')->integer()->required()->minValue(1)->maxValue(TourRules::MAX_PAX)->live(onBlur: true)
                            ->rules($price['pax_from'])->span('xs'),
                        TextInput::make('pax_to')->label('To (guests)')->integer()->minValue(1)->maxValue(TourRules::MAX_PAX)->live(onBlur: true)
                            ->rules($price['pax_to'])->span('xs'),
                        TextInput::make('vehicle')->placeholder('Car · Van · Mini coach')->maxLength(80)->live(onBlur: true)
                            ->rules($price['vehicle'])->span('md'),
                        TextInput::make('price_per_person')->label('Price per person')->prefix($currency)->numeric()->required()
                            ->minValue(1)->maxValue(100000)->live(onBlur: true)
                            ->rules($price['price_per_person'])->span('sm'),
                    ]),
                ]),
            AdminUi::grid([
                TagsInput::make('inclusions')->label('Included')->placeholder('e.g. Private A/C vehicle, then Enter')->reorderable()
                    ->tip('Up to 20 items (150 characters each).')
                    ->rules(FormRules::for($rules, 'inclusions'))->nestedRecursiveRules(TourRules::rules()['inclusions.*'])
                    ->span('lg'),
                TagsInput::make('exclusions')->label('Not included')->placeholder('e.g. International flights, then Enter')->reorderable()
                    ->tip('Up to 20 items (150 characters each).')
                    ->rules(FormRules::for($rules, 'exclusions'))->nestedRecursiveRules(TourRules::rules()['exclusions.*'])
                    ->span('lg'),
            ]),
        ];
    }

    /** Header of a price row: "1–2 guests · Car". */
    public static function priceRowLabel(array $state): string
    {
        $from = $state['pax_from'] ?? null;
        $to = $state['pax_to'] ?? null;
        $guests = match (true) {
            blank($from) => 'New group size',
            blank($to) => "{$from}+ guests",
            (int) $to === (int) $from => "{$from} ".((int) $from === 1 ? 'guest' : 'guests'),
            default => "{$from}–{$to} guests",
        };

        return filled($state['vehicle'] ?? null) ? "{$guests} · {$state['vehicle']}" : $guests;
    }

    /** @param  array<array-key, mixed>  $prices */
    public static function priceFrom(array $prices): string
    {
        $min = collect($prices)->pluck('price_per_person')->filter(fn ($p) => is_numeric($p) && $p > 0)->min();

        return $min === null ? 'Add prices' : config('tours.currency').' '.number_format((float) $min).' per person';
    }

    /** @param  array<array-key, mixed>  $days */
    public static function points(array $days): Collection
    {
        return collect($days)->pluck('destination_ids')->flatten()->filter()->unique()->values();
    }
}
