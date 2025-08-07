<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class ManageResellerDiscounts extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string $resource = UserResource::class;
    protected static string $view = 'filament.resources.user-resource.pages.manage-reseller-discounts';
    protected static ?string $title = 'Manajer Diskon Reseller';
    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Repeater::make('reseller_rules')
                    ->label('Aturan Diskon per Reseller')
                    ->schema([
                        Select::make('reseller_id')
                            ->label('Pilih Reseller')
                            ->options(User::whereHas('roles', fn($q) => $q->where('name', 'Reseller'))->pluck('name', 'id'))
                            ->searchable()->required()->columnSpanFull(),

                        TextInput::make('general_discount')->label('Diskon Umum (%)')
                            ->numeric()->minValue(0)->maxValue(100)->step(0.01)
                            ->helperText('Diskon fallback terendah.'),

                        Repeater::make('category_discounts')->label('Diskon Khusus per Kategori')
                            ->schema([
                                Select::make('category_id')->label('Kategori')->options(Category::pluck('name', 'id'))->required()->searchable(),
                                TextInput::make('discount_percentage')->label('Diskon (%)')->numeric()->required()->minValue(0)->maxValue(100)->step(0.01),
                            ])->columns(2)->addActionLabel('Tambah Aturan Kategori'),

                        Repeater::make('product_discounts')->label('Diskon Spesifik per Item (Prioritas Tertinggi)')
                            ->schema([
                                Select::make('product_id')->label('Produk')->options(Product::pluck('name', 'id'))->required()->searchable(),
                                TextInput::make('discount_percentage')->label('Diskon (%)')->numeric()->required()->minValue(0)->maxValue(100)->step(0.01),
                            ])->columns(2)->addActionLabel('Tambah Aturan Item'),
                    ])
                    ->addActionLabel('Tambah Aturan untuk Reseller Lain'),
            ])
            ->statePath('data');
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')->label('Simpan Semua Perubahan')->submit('save'),
        ];
    }

    public function save(): void
    {
        $resellerRules = $this->form->getState()['reseller_rules'] ?? [];
        DB::transaction(function () use ($resellerRules) {
            foreach ($resellerRules as $rule) {
                $resellerId = $rule['reseller_id'];
                if (is_numeric($rule['general_discount'])) {
                    User::find($resellerId)->update(['discount_percentage' => $rule['general_discount']]);
                }
                foreach ($rule['category_discounts'] ?? [] as $catRule) {
                    DB::table('reseller_category_discounts')->updateOrInsert(
                        ['user_id' => $resellerId, 'category_id' => $catRule['category_id']],
                        ['discount_percentage' => $catRule['discount_percentage']]
                    );
                }
                foreach ($rule['product_discounts'] ?? [] as $prodRule) {
                    DB::table('reseller_product_discounts')->updateOrInsert(
                        ['user_id' => $resellerId, 'product_id' => $prodRule['product_id']],
                        ['discount_percentage' => $prodRule['discount_percentage']]
                    );
                }
            }
        });
        Notification::make()->title('Semua aturan diskon berhasil disimpan!')->success()->send();
    }
}
