<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

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
                    Infolists\Components\TextEntry::make('tax_amount')->money('BDT')->visible(fn ($record) => $record->tax_amount > 0),
                    Infolists\Components\TextEntry::make('total')->money('BDT')->weight('bold'),
                    Infolists\Components\TextEntry::make('refunded_amount')->money('BDT')->visible(fn ($record) => $record->refunded_amount > 0)->color('danger'),
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
                Tables\Filters\SelectFilter::make('customer_id')
                    ->label('Customer')
                    ->searchable()
                    ->options(fn () => Customer::where('store_id', Store::current()->id)->pluck('name', 'id')),
                Tables\Filters\Filter::make('product')
                    ->form([
                        Forms\Components\Select::make('product_id')
                            ->label('Product')
                            ->searchable()
                            ->options(fn () => Product::where('store_id', Store::current()->id)->pluck('name', 'id')),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when(
                        $data['product_id'] ?? null,
                        fn (Builder $q, $productId) => $q->whereHas('items', fn ($q2) => $q2->where('product_id', $productId))
                    )),
                Tables\Filters\Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('created_at', '<=', $date))),
                Tables\Filters\Filter::make('amount_range')
                    ->form([
                        Forms\Components\TextInput::make('min')->numeric()->label('Min Amount'),
                        Forms\Components\TextInput::make('max')->numeric()->label('Max Amount'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['min'] ?? null, fn (Builder $q, $min) => $q->where('total', '>=', $min))
                        ->when($data['max'] ?? null, fn (Builder $q, $max) => $q->where('total', '<=', $max))),
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
                Tables\Actions\Action::make('cancel_order')
                    ->label('Cancel Order')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => ! in_array($record->status, ['cancelled', 'delivered', 'returned']))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Cancellation reason')
                            ->required()
                            ->rows(2),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update(['status' => 'cancelled']);
                        $record->addStatusHistory('cancelled', $data['reason'], auth()->user()->name);
                    }),
                Tables\Actions\Action::make('refund')
                    ->label('Refund')
                    ->icon('heroicon-o-banknotes')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Order $record) => $record->payment_status === 'paid' && $record->refunded_amount < $record->total)
                    ->action(function (Order $record) {
                        $record->update(['refunded_amount' => $record->total, 'payment_status' => 'refunded']);
                        $record->addStatusHistory($record->status, "Full refund of ৳{$record->total}", auth()->user()->name);
                        Notification::make()->title('Order refunded')->success()->send();
                    }),
                Tables\Actions\Action::make('partial_refund')
                    ->label('Partial Refund')
                    ->icon('heroicon-o-banknotes')
                    ->color('warning')
                    ->visible(fn (Order $record) => in_array($record->payment_status, ['paid', 'refunded']) && $record->refundable_amount > 0)
                    ->form(fn (Order $record) => [
                        Forms\Components\TextInput::make('amount')
                            ->label("Refund amount (max ৳{$record->refundable_amount})")
                            ->numeric()
                            ->minValue(0.01)
                            ->maxValue($record->refundable_amount)
                            ->required(),
                    ])
                    ->action(function (Order $record, array $data) {
                        $amount = min((float) $data['amount'], $record->refundable_amount);
                        $newRefunded = $record->refunded_amount + $amount;
                        $record->update([
                            'refunded_amount' => $newRefunded,
                            'payment_status' => $newRefunded >= $record->total ? 'refunded' : $record->payment_status,
                        ]);
                        $record->addStatusHistory($record->status, "Partial refund of ৳{$amount}", auth()->user()->name);
                        Notification::make()->title("Refunded ৳{$amount}")->success()->send();
                    }),
                Tables\Actions\Action::make('print_invoice')
                    ->label('Invoice')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn (Order $record) => route('admin.orders.invoice', $record))
                    ->openUrlInNewTab(),
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
