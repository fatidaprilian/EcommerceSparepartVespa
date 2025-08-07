<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Navigation\NavigationItem;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        // Field untuk diskon sudah dihapus dari sini agar terpusat
        // di halaman "Manajer Diskon"
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\Select::make('roles')
                    ->relationship('roles', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->dehydrated(fn($state) => filled($state))
                    ->required(fn(string $context): bool => $context === 'create'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('email')->searchable(),
                Tables\Columns\TextColumn::make('roles.name')->badge()->searchable(),
                Tables\Columns\TextColumn::make('discount_percentage')
                    ->label('Diskon Umum (%)')
                    ->sortable()
                    ->formatStateUsing(fn($state): string => number_format((float)$state, 2) . '%'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
            'manage-discounts' => Pages\ManageResellerDiscounts::route('/manage-discounts'),
        ];
    }

    public static function getNavigationItems(): array
    {
        $items = parent::getNavigationItems();
        $items[] = NavigationItem::make('Manajer Diskon')
            ->group(static::getNavigationGroup())
            ->icon('heroicon-o-sparkles')
            ->url(static::getUrl('manage-discounts'))
            ->isActiveWhen(fn() => request()->routeIs(static::getRouteBaseName() . '.manage-discounts'));
        return $items;
    }

    public static function getRelations(): array
    {
        return [];
    }
}
