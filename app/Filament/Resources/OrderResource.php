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

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Order Status')
                ->schema([
                    Forms\Components\Select::make('status')
                        ->options(Order::STATUSES)
                        ->required(),

                    Forms\Components\Select::make('payment_status')
                        ->options(Order::PAYMENT_STATUSES)
                        ->required(),

                    Forms\Components\TextInput::make('courier_name')
                        ->placeholder('Pathao / Steadfast / RedX'),

                    Forms\Components\TextInput::make('courier_tracking_id')
                        ->label('Tracking ID'),

                    Forms\Components\Textarea::make('admin_notes')
                        ->rows(2)
                        ->columnSpanFull(),
                ])->columns(2),

            Forms\Components\Section::make('COD Confirmation')
                ->schema([
                    Forms\Components\DateTimePicker::make('cod_confirmed_at'),
                    Forms\Components\TextInput::make('cod_confirmed_by')
                        ->placeholder('Agent name'),
                ])->columns(2)
                ->visible(fn ($record) => $record?->payment_method === 'cod'),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Order Summary')
                ->schema([
                    Infolists\Components\TextEntry::make('order_number')->weight('bold'),
                    Infolists\Components\TextEntry::make('status')
                        ->badge()
                        ->color(fn ($record) => $record->status_color),
                    Infolists\Components\TextEntry::make('payment_method')
                        ->formatStateUsing(fn ($state) => Order::PAYMENT_METHODS[$state] ?? $state),
                    Infolists\Components\TextEntry::make('payment_status')
                        ->badge()
                        ->color(fn ($state) => match ($state) {
                            'paid' => 'success', 'unpaid' => 'warning',
                            'failed' => 'danger', default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('subtotal')->money('BDT'),
                    Infolists\Components\TextEntry::make('discount_amount')->money('BDT'),
                    Infolists\Components\TextEntry::make('delivery_charge')->money('BDT'),
                    Infolists\Components\TextEntry::make('total')->money('BDT')->weight('bold'),
                    Infolists\Components\TextEntry::make('created_at')->dateTime(),
                ])->columns(3),

            Infolists\Components\Section::make('Customer & Delivery')
                ->schema([
                    Infolists\Components\TextEntry::make('customer_name'),
                    Infolists\Components\TextEntry::make('customer_phone'),
                    Infolists\Components\TextEntry::make('customer_email')->placeholder('—'),
                    Infolists\Components\TextEntry::make('address_line')
                        ->label('Address')
                        ->formatStateUsing(fn ($record) => implode(', ', array_filter([
                            $record->address_line,
                            $record->area,
                            $record->thana_name,
                            $record->district_name,
                            $record->division_name,
                        ])))
                        ->columnSpan(2),
                ])->columns(3),

            Infolists\Components\RepeatableEntry::make('items')
                ->schema([
                    Infolists\Components\TextEntry::make('product_name')->weight('bold'),
                    Infolists\Components\TextEntry::make('variant_label')->placeholder('—'),
                    Infolists\Components\TextEntry::make('unit_price')->money('BDT'),
                    Infolists\Components\TextEntry::make('quantity'),
                    Infolists\Components\TextEntry::make('subtotal')->money('BDT'),
                ])
                ->columns(5),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->searchable()
                    ->weight('bold')
                    ->copyable(),

                Tables\Columns\TextColumn::make('customer_name')->searchable(),
                Tables\Columns\TextColumn::make('customer_phone')->searchable(),

                Tables\Columns\TextColumn::make('district_name')
                    ->label('District'),

                Tables\Columns\TextColumn::make('total')
                    ->money('BDT')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'info' => fn ($state) => in_array($state, ['confirmed', 'processing']),
                        'primary' => 'shipped',
                        'success' => 'delivered',
                        'danger' => fn ($state) => in_array($state, ['cancelled', 'returned']),
                    ]),

                Tables\Columns\BadgeColumn::make('payment_method')
                    ->colors(['gray' => 'cod', 'success' => 'sslcommerz', 'primary' => 'bkash'])
                    ->formatStateUsing(fn ($state) => Order::PAYMENT_METHODS[$state] ?? $state),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(Order::STATUSES),
                Tables\Filters\SelectFilter::make('payment_method')->options(Order::PAYMENT_METHODS),
                Tables\Filters\SelectFilter::make('payment_status')->options(Order::PAYMENT_STATUSES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('confirm_cod')
                    ->label('Confirm COD')
                    ->icon('heroicon-o-phone')
                    ->color('success')
                    ->visible(fn ($record) => $record->payment_method === 'cod' && ! $record->cod_confirmed_at && $record->status === 'pending')
                    ->action(function ($record) {
                        $record->update([
                            'cod_confirmed_at' => now(),
                            'cod_confirmed_by' => auth()->user()->name,
                            'status' => 'confirmed',
                        ]);
                        $record->addStatusHistory('confirmed', 'COD confirmed by agent', auth()->user()->name);
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('mark_confirmed')
                        ->label('Mark Confirmed')
                        ->action(fn ($records) => $records->each(fn ($r) => $r->update(['status' => 'confirmed']))),
                    Tables\Actions\BulkAction::make('mark_shipped')
                        ->label('Mark Shipped')
                        ->action(fn ($records) => $records->each(fn ($r) => $r->update(['status' => 'shipped', 'shipped_at' => now()]))),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
