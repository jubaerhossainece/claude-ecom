<?php

namespace App\Filament\Resources\WarehouseResource\Pages;

use App\Filament\Resources\WarehouseResource;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Store;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Filament\Actions;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\DB;

class ListWarehouses extends ListRecords
{
    protected static string $resource = WarehouseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('transfer_stock')
                ->label('Transfer Stock')
                ->icon('heroicon-o-arrows-right-left')
                ->color('gray')
                ->form([
                    Forms\Components\Select::make('product_id')
                        ->label('Product')
                        ->searchable()
                        ->live()
                        ->options(fn () => Product::where('store_id', Store::current()->id)->pluck('name', 'id'))
                        ->required(),

                    Forms\Components\Select::make('variant_id')
                        ->label('Variant')
                        ->options(fn (Forms\Get $get) => $get('product_id')
                            ? ProductVariant::where('product_id', $get('product_id'))->get()->pluck('variant_label', 'id')
                            : [])
                        ->visible(fn (Forms\Get $get) => $get('product_id') && ProductVariant::where('product_id', $get('product_id'))->exists())
                        ->required(fn (Forms\Get $get) => $get('product_id') && ProductVariant::where('product_id', $get('product_id'))->exists()),

                    Forms\Components\Select::make('source_warehouse_id')
                        ->label('From Warehouse')
                        ->options(fn () => Warehouse::where('store_id', Store::current()->id)->pluck('name', 'id'))
                        ->required(),

                    Forms\Components\Select::make('destination_warehouse_id')
                        ->label('To Warehouse')
                        ->options(fn () => Warehouse::where('store_id', Store::current()->id)->pluck('name', 'id'))
                        ->different('source_warehouse_id')
                        ->required(),

                    Forms\Components\TextInput::make('quantity')
                        ->numeric()
                        ->minValue(1)
                        ->required(),

                    Forms\Components\TextInput::make('reason')
                        ->label('Reason (optional)')
                        ->placeholder('e.g. Rebalancing stock ahead of a promotion'),
                ])
                ->action(function (array $data) {
                    $store = Store::current();
                    $variantId = $data['variant_id'] ?: null;

                    $sourceStock = WarehouseStock::firstOrCreate(
                        ['warehouse_id' => $data['source_warehouse_id'], 'product_id' => $data['product_id'], 'variant_id' => $variantId],
                        ['quantity' => 0, 'reserved_quantity' => 0]
                    );

                    $available = $sourceStock->quantity - $sourceStock->reserved_quantity;
                    $qty = (int) $data['quantity'];

                    if ($qty > $available) {
                        Notification::make()
                            ->title("Only {$available} unit(s) available to transfer from that warehouse")
                            ->danger()
                            ->send();

                        return;
                    }

                    $destStock = WarehouseStock::firstOrCreate(
                        ['warehouse_id' => $data['destination_warehouse_id'], 'product_id' => $data['product_id'], 'variant_id' => $variantId],
                        ['quantity' => 0, 'reserved_quantity' => 0]
                    );

                    DB::transaction(function () use ($sourceStock, $destStock, $qty, $data, $variantId, $store) {
                        $sourceStock->decrement('quantity', $qty);
                        $destStock->increment('quantity', $qty);

                        InventoryMovement::create([
                            'store_id' => $store->id,
                            'warehouse_id' => $data['source_warehouse_id'],
                            'product_id' => $data['product_id'],
                            'variant_id' => $variantId,
                            'type' => 'transfer_out',
                            'quantity_change' => -$qty,
                            'quantity_after' => $sourceStock->fresh()->quantity,
                            'reason' => $data['reason'] ?: null,
                            'created_by' => auth()->user()?->name ?? 'Admin',
                        ]);

                        InventoryMovement::create([
                            'store_id' => $store->id,
                            'warehouse_id' => $data['destination_warehouse_id'],
                            'product_id' => $data['product_id'],
                            'variant_id' => $variantId,
                            'type' => 'transfer_in',
                            'quantity_change' => $qty,
                            'quantity_after' => $destStock->fresh()->quantity,
                            'reason' => $data['reason'] ?: null,
                            'created_by' => auth()->user()?->name ?? 'Admin',
                        ]);
                    });

                    Notification::make()
                        ->title("Transferred {$qty} unit(s)")
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
