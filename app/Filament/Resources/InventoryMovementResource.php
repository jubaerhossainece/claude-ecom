<?php

namespace App\Filament\Resources;

use App\Filament\Resources\InventoryMovementResource\Pages;
use App\Models\InventoryMovement;
use App\Models\Warehouse;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class InventoryMovementResource extends Resource
{
    protected static ?string $model = InventoryMovement::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationLabel = 'Inventory Movements';
    protected static ?string $navigationGroup = 'Inventory';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('type')
                    ->formatStateUsing(fn ($state) => InventoryMovement::TYPES[$state] ?? $state)
                    ->colors([
                        'danger' => fn ($state) => in_array($state, ['sale', 'damaged']),
                        'success' => fn ($state) => in_array($state, ['restock', 'return', 'transfer_in']),
                        'warning' => 'adjustment',
                        'gray' => 'transfer_out',
                    ]),

                Tables\Columns\TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->description(fn ($record) => $record->variant?->variant_label),

                Tables\Columns\TextColumn::make('warehouse.name')
                    ->label('Warehouse'),

                Tables\Columns\TextColumn::make('quantity_change')
                    ->label('Change')
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger')
                    ->formatStateUsing(fn ($state) => ($state >= 0 ? '+' : '') . $state),

                Tables\Columns\TextColumn::make('quantity_after')
                    ->label('Balance'),

                Tables\Columns\TextColumn::make('order.order_number')
                    ->label('Order')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('reason')
                    ->placeholder('—')
                    ->limit(40),

                Tables\Columns\TextColumn::make('created_by')
                    ->label('By')
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')->options(InventoryMovement::TYPES),
                Tables\Filters\SelectFilter::make('warehouse_id')
                    ->label('Warehouse')
                    ->options(fn () => Warehouse::pluck('name', 'id')),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInventoryMovements::route('/'),
        ];
    }
}
