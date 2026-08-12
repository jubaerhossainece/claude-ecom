<?php

namespace App\Filament\Pages;

use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class StoreSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static string $view = 'filament.pages.store-settings';
    protected static ?string $navigationGroup = 'Configuration';
    protected static ?int $navigationSort = 1;
    protected static ?string $title = 'Store Settings';

    public array $data = [];

    public function mount(): void
    {
        $store = Store::current();
        $this->data = [
            'name' => $store->name,
            'tagline' => $store->tagline,
            'description' => $store->description,
            'support_phone' => $store->getSetting('support_phone'),
            'support_email' => $store->getSetting('support_email'),
            'currency' => $store->getSetting('currency', 'BDT'),
            'currency_symbol' => $store->getSetting('currency_symbol', '৳'),
            'primary_color' => $store->getSetting('primary_color', '#16a34a'),
            'default_language' => $store->getSetting('default_language', 'en'),
            'payment_cod_enabled' => (bool) $store->getSetting('payment_cod_enabled', true),
            'payment_sslcommerz_enabled' => (bool) $store->getSetting('payment_sslcommerz_enabled', false),
            'payment_bkash_enabled' => (bool) $store->getSetting('payment_bkash_enabled', false),
            'cod_confirmation_required' => (bool) $store->getSetting('cod_confirmation_required', true),
            'cod_confirmation_message' => $store->getSetting('cod_confirmation_message'),
            'delivery_inside_dhaka' => $store->getSetting('delivery_inside_dhaka', 60),
            'delivery_outside_dhaka' => $store->getSetting('delivery_outside_dhaka', 120),
            'free_delivery_above' => $store->getSetting('free_delivery_above', 0),
            'tax_enabled' => (bool) $store->getSetting('tax_enabled', false),
            'tax_rate_percent' => $store->getSetting('tax_rate_percent', 0),
            'meta_title' => $store->getSetting('meta_title'),
            'meta_description' => $store->getSetting('meta_description'),
        ];
        $this->form->fill($this->data);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make()->tabs([
                    Forms\Components\Tabs\Tab::make('General')
                        ->schema([
                            Forms\Components\TextInput::make('name')->required(),
                            Forms\Components\TextInput::make('tagline'),
                            Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
                            Forms\Components\TextInput::make('support_phone')->tel(),
                            Forms\Components\TextInput::make('support_email')->email(),
                            Forms\Components\Select::make('default_language')
                                ->options(['en' => 'English', 'bn' => 'বাংলা'])
                                ->required(),
                            Forms\Components\ColorPicker::make('primary_color')
                                ->label('Theme Color'),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('Currency')
                        ->schema([
                            Forms\Components\TextInput::make('currency')->default('BDT'),
                            Forms\Components\TextInput::make('currency_symbol')->default('৳'),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('Payment Methods')
                        ->schema([
                            Forms\Components\Toggle::make('payment_cod_enabled')
                                ->label('Cash on Delivery')
                                ->default(true),
                            Forms\Components\Toggle::make('cod_confirmation_required')
                                ->label('Require phone confirmation for COD'),
                            Forms\Components\Textarea::make('cod_confirmation_message')
                                ->label('COD Confirmation SMS/Note')
                                ->rows(2)
                                ->columnSpanFull(),
                            Forms\Components\Toggle::make('payment_sslcommerz_enabled')
                                ->label('SSLCommerz (Card/Net Banking)'),
                            Forms\Components\Toggle::make('payment_bkash_enabled')
                                ->label('bKash Mobile Banking'),
                        ]),

                    Forms\Components\Tabs\Tab::make('Delivery')
                        ->schema([
                            Forms\Components\TextInput::make('delivery_inside_dhaka')
                                ->label('Inside Dhaka charge (৳)')
                                ->numeric(),
                            Forms\Components\TextInput::make('delivery_outside_dhaka')
                                ->label('Outside Dhaka charge (৳)')
                                ->numeric(),
                            Forms\Components\TextInput::make('free_delivery_above')
                                ->label('Free delivery above (৳, 0 = disabled)')
                                ->numeric(),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('Tax')
                        ->schema([
                            Forms\Components\Toggle::make('tax_enabled')
                                ->label('Charge tax on orders')
                                ->live(),
                            Forms\Components\TextInput::make('tax_rate_percent')
                                ->label('Tax rate (%)')
                                ->numeric()
                                ->minValue(0)
                                ->maxValue(100)
                                ->visible(fn (Forms\Get $get) => $get('tax_enabled')),
                        ])->columns(2),

                    Forms\Components\Tabs\Tab::make('SEO')
                        ->schema([
                            Forms\Components\TextInput::make('meta_title')->columnSpanFull(),
                            Forms\Components\Textarea::make('meta_description')->rows(3)->columnSpanFull(),
                        ]),
                ])->columnSpanFull(),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $store = Store::current();

        $store->update([
            'name' => $data['name'],
            'tagline' => $data['tagline'] ?? null,
            'description' => $data['description'] ?? null,
        ]);

        $stringSettings = ['support_phone', 'support_email', 'currency', 'currency_symbol', 'primary_color', 'default_language', 'cod_confirmation_message', 'meta_title', 'meta_description'];
        $boolSettings = ['payment_cod_enabled', 'payment_sslcommerz_enabled', 'payment_bkash_enabled', 'cod_confirmation_required'];
        $intSettings = ['delivery_inside_dhaka', 'delivery_outside_dhaka', 'free_delivery_above', 'tax_rate_percent'];

        foreach ($stringSettings as $key) {
            $store->setSetting($key, $data[$key] ?? '', 'string', $this->getGroup($key));
        }
        foreach ($boolSettings as $key) {
            $store->setSetting($key, $data[$key] ? '1' : '0', 'boolean', 'payment');
        }
        foreach ($intSettings as $key) {
            $store->setSetting($key, (string) ($data[$key] ?? 0), 'integer', $this->getGroup($key));
        }

        $store->setSetting('tax_enabled', $data['tax_enabled'] ? '1' : '0', 'boolean', 'tax');

        Notification::make()->title('Settings saved')->success()->send();
    }

    private function getGroup(string $key): string
    {
        return match (true) {
            str_starts_with($key, 'payment_') || str_starts_with($key, 'cod_') => 'payment',
            str_starts_with($key, 'delivery_') || str_starts_with($key, 'free_') => 'delivery',
            str_starts_with($key, 'tax_') => 'tax',
            in_array($key, ['meta_title', 'meta_description']) => 'seo',
            in_array($key, ['primary_color', 'theme']) => 'appearance',
            default => 'general',
        };
    }
}
