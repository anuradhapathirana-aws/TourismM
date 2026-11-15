<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Support\AdminUi;
use App\Filament\Support\FormRules;
use App\Models\User;
use App\Support\Validation\UserRules;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Admin staff accounts + roles (Super Admin only). */
final class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Access';

    protected static ?string $navigationLabel = 'Admin users';

    public static function form(Form $form): Form
    {
        $rules = fn (?Model $record): array => UserRules::rules($record instanceof User ? $record : null);
        $min = UserRules::passwordMinLength();

        return $form->schema([
            Section::make('Admin user')->schema([
                AdminUi::grid([
                    TextInput::make('name')->required()->minLength(2)->maxLength(100)
                        ->rules(FormRules::for($rules, 'name'))
                        ->dehydrateStateUsing(fn (string $state) => trim(strip_tags($state)))
                        ->span('md'),
                    TextInput::make('email')->email()->required()->maxLength(150)
                        ->rules(FormRules::for($rules, 'email'))
                        ->dehydrateStateUsing(fn (string $state) => strtolower(trim($state)))
                        ->span('md'),
                    Select::make('roles')
                        ->relationship('roles', 'name')
                        ->multiple()
                        ->preload()
                        ->required()
                        ->rules(FormRules::for($rules, 'roles'))
                        ->nestedRecursiveRules(UserRules::rules()['roles.*'])
                        ->tip('super-admin: everything · content-editor: locations · inquiry-manager: enquiries (coming)')
                        ->span('md'),
                    TextInput::make('password')
                        ->password()
                        ->revealable()
                        ->minLength($min)
                        ->maxLength(255)
                        ->rules(FormRules::for($rules, 'password'))
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(fn (?string $state) => filled($state))
                        ->tip(fn (string $operation) => ($operation === 'edit' ? 'Leave blank to keep the current password. ' : '')
                            ."At least {$min} characters".(app()->isProduction() ? ' with upper and lower case, numbers and symbols.' : '.'))
                        ->confirmed()
                        ->validationMessages(['confirmed' => 'The passwords do not match.'])
                        ->extraInputAttributes(['data-lg-wait-for' => 'password_confirmation'], merge: true)
                        ->span('md'),
                    TextInput::make('password_confirmation')
                        ->label('Confirm password')
                        ->password()
                        ->revealable()
                        ->required(fn (string $operation) => $operation === 'create')
                        ->dehydrated(false)
                        ->extraInputAttributes(['data-lg-match' => 'password'], merge: true)
                        ->span('md'),
                    Toggle::make('is_active')->label('Active')->default(true)
                        ->rules(FormRules::for($rules, 'is_active'))
                        ->tip('Inactive users cannot sign in. You can’t deactivate yourself.')
                        ->disabled(fn (?User $record) => $record?->is(auth()->user()))
                        ->span('xs'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with('roles:id,name'))
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->badge(),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
                Tables\Columns\TextColumn::make('last_login_at')->since()->placeholder('Never'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
