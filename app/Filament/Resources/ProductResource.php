<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Product;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Product Details')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn(string $operation, $state, Forms\Set $set) => $operation === 'create' ? $set('slug', Str::slug($state)) : null),

                                Forms\Components\TextInput::make('slug')
                                    ->required()
                                    ->unique(Product::class, 'slug', ignoreRecord: true),

                                Forms\Components\Textarea::make('description')
                                    ->rows(5)
                                    ->columnSpanFull(),
                            ]),

                        Forms\Components\Section::make('Pricing & Stock')
                            ->schema([
                                Forms\Components\TextInput::make('base_price')
                                    ->label('Price (in Rupiah)')
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp'),

                                Forms\Components\TextInput::make('stock')
                                    ->required()
                                    ->numeric()
                                    ->default(0),
                            ]),
                    ])->columnSpan(2),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make('Status & Category')
                            ->schema([
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Active')
                                    ->default(true)
                                    ->required(),

                                Forms\Components\Select::make('category_id')
                                    ->relationship('category', 'name')
                                    ->searchable()
                                    ->required(),
                            ]),

                        Forms\Components\Section::make('ERP Information')
                            ->schema([
                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU (Stock Keeping Unit)')
                                    ->required()
                                    ->unique(Product::class, 'sku', ignoreRecord: true),

                                Forms\Components\TextInput::make('erp_id')
                                    ->label('ERP ID')
                                    ->unique(Product::class, 'erp_id', ignoreRecord: true),
                            ]),

                        // Placeholder untuk gambar
                        Forms\Components\Section::make('Image')
                            ->schema([
                                Forms\Components\FileUpload::make('image_url')
                                    ->label('Product Image')
                                    ->image()
                                    ->disk('public') // Pastikan Anda memiliki 'public' disk
                                    ->directory('product-images'),
                            ]),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image')
                    ->disk('public'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('base_price')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('stock')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean(),
            ])
            ->filters([
                //
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
