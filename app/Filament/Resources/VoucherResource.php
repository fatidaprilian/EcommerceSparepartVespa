<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Marketing'; // Mengelompokkannya di menu terpisah

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Detail Voucher')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Kode Voucher')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi')
                            ->columnSpanFull(),
                    ])->columns(1),

                Forms\Components\Section::make('Aturan Diskon')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->label('Tipe Diskon')
                            ->options([
                                'fixed' => 'Potongan Harga Tetap (Rp)',
                                'percentage' => 'Potongan Persentase (%)',
                            ])
                            ->required()
                            ->default('fixed'),
                        Forms\Components\TextInput::make('value')
                            ->label('Nilai Diskon')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('min_purchase')
                            ->label('Minimum Pembelian (Rp)')
                            ->numeric()
                            ->default(0)
                            ->prefix('Rp'),
                    ])->columns(3),

                Forms\Components\Section::make('Segmentasi & Batasan')
                    ->schema([
                        Forms\Components\Select::make('roles')
                            ->label('Berlaku untuk Role (Segmentasi)')
                            ->multiple()
                            ->relationship('roles', 'name')
                            ->preload()
                            ->helperText('Kosongkan jika berlaku untuk semua pengguna.'),
                        Forms\Components\TextInput::make('max_uses')
                            ->label('Batas Penggunaan Total')
                            ->numeric()
                            ->helperText('Kosongkan jika tidak ada batas.'),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Tanggal Kedaluwarsa'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Aktifkan Voucher')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')->searchable(),
                Tables\Columns\TextColumn::make('type')->badge(),
                Tables\Columns\TextColumn::make('value')
                    ->money(fn(Voucher $record) => $record->type === 'fixed' ? 'IDR' : null)
                    ->formatStateUsing(fn(Voucher $record, $state) => $record->type === 'percentage' ? "{$state}%" : $state)
                    ->sortable(),
                Tables\Columns\TextColumn::make('uses')->label('Digunakan')->sortable(),
                Tables\Columns\TextColumn::make('max_uses')->label('Batas'),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('expires_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVouchers::route('/'),
            'create' => Pages\CreateVoucher::route('/create'),
            'edit' => Pages\EditVoucher::route('/{record}/edit'),
        ];
    }
}
