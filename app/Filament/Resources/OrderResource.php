<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('tracking_number')
                    ->label('Nomor Resi Pengiriman'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Customer')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('payment_status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('total_amount')->money('IDR')->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->actions([
                Tables\Actions\ViewAction::make(), // Tombol ini akan menggunakan infolist()
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Informasi Pesanan')
                    ->schema([
                        Infolists\Components\TextEntry::make('order_number'),
                        Infolists\Components\TextEntry::make('user.name')->label('Customer'),
                        Infolists\Components\TextEntry::make('created_at')->dateTime(),
                        Infolists\Components\TextEntry::make('status')->badge(),
                        Infolists\Components\TextEntry::make('payment_status')->badge(),
                    ])->columns(2),

                Infolists\Components\Section::make('Rincian Biaya')
                    ->schema([
                        Infolists\Components\TextEntry::make('total_amount')->money('IDR'),
                        Infolists\Components\TextEntry::make('shipping_cost')->money('IDR'),
                        Infolists\Components\TextEntry::make('grand_total')
                            ->label('Grand Total')
                            ->money('IDR')
                            ->state(fn(Order $record): float => $record->total_amount + $record->shipping_cost),
                    ])->columns(3),

                Infolists\Components\Section::make('Verifikasi Pembayaran Manual')
                    ->visible(fn(Order $record) => $record->payment_status === 'pending_verification')
                    ->schema([
                        Infolists\Components\ImageEntry::make('payment_proof')
                            ->label('Bukti Transfer')
                            ->disk('public')
                            ->height(250),
                        Infolists\Components\TextEntry::make('payment_metadata.bank_name')->label('Nama Bank'),
                        Infolists\Components\TextEntry::make('payment_metadata.account_name')->label('Nama Rekening'),
                        Infolists\Components\TextEntry::make('payment_metadata.transfer_date')->label('Tanggal Transfer')->date(),
                    ])->columns(2),
            ]);
    }

    public static function getPages(): array
    {
        // Hapus baris 'view' yang menyebabkan error
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            // 'view' => Pages\ViewOrder::route('/{record}'), // <-- BARIS INI DIHAPUS
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }
}
