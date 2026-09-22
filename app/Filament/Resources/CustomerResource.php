<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Customers';

    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withCount('orders')
            ->withSum('orders', 'total')
            ->withMax('orders', 'created_at');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Customer Details')
                ->schema([
                    Forms\Components\TextInput::make('name')->required(),
                    Forms\Components\TextInput::make('phone')->tel()->required(),
                    Forms\Components\TextInput::make('email')->email(),
                    Forms\Components\Toggle::make('is_active')->default(true),
                ])->columns(2),

            Forms\Components\Section::make('Admin Notes')
                ->schema([
                    Forms\Components\Textarea::make('admin_notes')
                        ->hiddenLabel()
                        ->rows(3)
                        ->placeholder('Internal notes about this customer — not visible to them.'),
                ]),
        ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Customer')
                ->schema([
                    Infolists\Components\TextEntry::make('name')->weight('bold'),
                    Infolists\Components\TextEntry::make('phone'),
                    Infolists\Components\TextEntry::make('email')->placeholder('—'),
                    Infolists\Components\IconEntry::make('is_active')->boolean(),
                    Infolists\Components\TextEntry::make('segment')
                        ->badge()
                        ->formatStateUsing(fn ($state) => Customer::SEGMENTS[$state] ?? $state)
                        ->color(fn ($state) => match ($state) {
                            'vip' => 'success',
                            'new' => 'info',
                            'returning' => 'primary',
                            'inactive' => 'danger',
                            default => 'gray',
                        }),
                    Infolists\Components\TextEntry::make('lifetime_spend')->label('Lifetime Spend')->money('BDT'),
                    Infolists\Components\TextEntry::make('orders_count')->label('Orders'),
                    Infolists\Components\TextEntry::make('created_at')->label('Registered')->dateTime(),
                ])->columns(3),

            Infolists\Components\Section::make('Admin Notes')
                ->schema([
                    Infolists\Components\TextEntry::make('admin_notes')->hiddenLabel()->placeholder('No notes yet.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('phone')->searchable(),
                Tables\Columns\TextColumn::make('email')->placeholder('—')->searchable(),
                Tables\Columns\TextColumn::make('orders_count')->label('Orders')->sortable(),
                Tables\Columns\TextColumn::make('orders_sum_total')->label('Lifetime Spend')->money('BDT')->sortable(),
                Tables\Columns\TextColumn::make('segment')
                    ->badge()
                    ->formatStateUsing(fn ($state) => Customer::SEGMENTS[$state] ?? $state)
                    ->color(fn ($state) => match ($state) {
                        'vip' => 'success',
                        'new' => 'info',
                        'returning' => 'primary',
                        'inactive' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_active')->boolean(),
                Tables\Columns\TextColumn::make('created_at')->label('Registered')->dateTime('d M Y')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
                Tables\Filters\SelectFilter::make('segment')
                    ->options(Customer::SEGMENTS)
                    ->query(function (Builder $query, array $data) {
                        return match ($data['value'] ?? null) {
                            'vip' => $query->havingRaw('COALESCE(orders_sum_total, 0) >= ?', [Customer::VIP_SPEND_THRESHOLD]),
                            'new' => $query->where('customers.created_at', '>=', now()->subDays(Customer::NEW_DAYS)),
                            'inactive' => $query->havingRaw('orders_max_created_at IS NOT NULL AND orders_max_created_at < ?', [now()->subDays(Customer::INACTIVE_DAYS)]),
                            'returning' => $query->having('orders_count', '>', 1),
                            default => $query,
                        };
                    }),
                Tables\Filters\Filter::make('registered_range')
                    ->form([
                        Forms\Components\DatePicker::make('from'),
                        Forms\Components\DatePicker::make('until'),
                    ])
                    ->query(fn (Builder $query, array $data) => $query
                        ->when($data['from'] ?? null, fn (Builder $q, $date) => $q->whereDate('customers.created_at', '>=', $date))
                        ->when($data['until'] ?? null, fn (Builder $q, $date) => $q->whereDate('customers.created_at', '<=', $date))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\AddressesRelationManager::class,
            RelationManagers\OrdersRelationManager::class,
            RelationManagers\WishlistRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
