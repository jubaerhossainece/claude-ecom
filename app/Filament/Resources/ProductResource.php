<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Brand;
use App\Models\Category;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Store;
use App\Models\Warehouse;
use App\Models\WarehouseStock;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $navigationGroup = 'Catalog';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Tabs::make()->tabs([
                Forms\Components\Tabs\Tab::make('Basic Info')
                    ->schema([
                        Forms\Components\Hidden::make('store_id')
                            ->default(fn () => Store::current()->id),

                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state)))
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->columnSpanFull(),

                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(fn () => Category::where('store_id', Store::current()->id)
                                ->active()
                                ->with('parent')
                                ->get()
                                ->mapWithKeys(fn ($cat) => [
                                    $cat->id => ($cat->parent ? $cat->parent->name.' › ' : '').$cat->name,
                                ]))
                            ->searchable()
                            ->required(),

                        Forms\Components\Select::make('brand_id')
                            ->label('Brand')
                            ->options(fn () => Brand::where('store_id', Store::current()->id)->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('None'),

                        Forms\Components\Select::make('status')
                            ->options(array_combine(Product::STATUSES, array_map(fn ($s) => ucfirst($s), Product::STATUSES)))
                            ->default('draft')
                            ->required(),

                        Forms\Components\Select::make('unit_of_sale')
                            ->options(array_combine(Product::UNITS, Product::UNITS))
                            ->default('piece')
                            ->required(),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured product'),

                        Forms\Components\Textarea::make('short_description')
                            ->rows(2)
                            ->columnSpanFull(),

                        Forms\Components\RichEditor::make('description')
                            ->columnSpanFull(),

                        Forms\Components\CheckboxList::make('tags')
                            ->relationship('tags', 'name')
                            ->columns(3)
                            ->gridDirection('row')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Tabs\Tab::make('Pricing & Inventory')
                    ->schema([
                        Forms\Components\TextInput::make('base_price')
                            ->numeric()
                            ->prefix('৳')
                            ->required(),

                        Forms\Components\TextInput::make('sale_price')
                            ->numeric()
                            ->prefix('৳')
                            ->placeholder('Leave empty for no sale'),

                        Forms\Components\TextInput::make('cost_price')
                            ->numeric()
                            ->prefix('৳')
                            ->placeholder('Internal cost tracking'),

                        Forms\Components\TextInput::make('sku')
                            ->label('SKU')
                            ->placeholder('Auto-generated if empty'),

                        Forms\Components\Toggle::make('track_inventory')
                            ->default(true)
                            ->live(),

                        Forms\Components\Toggle::make('allow_backorder')
                            ->label('Allow backorder (sell when out of stock)'),

                        Forms\Components\Placeholder::make('stock_quantity_note')
                            ->label('Stock Quantity')
                            ->content('Managed per warehouse — use the "Manage Stock" action from the product list.')
                            ->visible(fn (Forms\Get $get) => $get('track_inventory')),

                        Forms\Components\TextInput::make('low_stock_threshold')
                            ->numeric()
                            ->default(5)
                            ->visible(fn (Forms\Get $get) => $get('track_inventory')),

                        Forms\Components\TextInput::make('weight')
                            ->numeric()
                            ->suffix('kg')
                            ->placeholder('Optional'),

                        Forms\Components\KeyValue::make('dimensions')
                            ->label('Dimensions (cm)')
                            ->keyLabel('Dimension')
                            ->valueLabel('Value')
                            ->addActionLabel('Add dimension'),
                    ])->columns(2),

                Forms\Components\Tabs\Tab::make('Images')
                    ->schema([
                        Forms\Components\SpatieMediaLibraryFileUpload::make('thumbnail')
                            ->collection('thumbnail')
                            ->label('Thumbnail Image')
                            ->image()
                            ->imageEditor()
                            ->columnSpanFull(),

                        Forms\Components\SpatieMediaLibraryFileUpload::make('images')
                            ->collection('images')
                            ->label('Gallery Images')
                            ->image()
                            ->multiple()
                            ->reorderable()
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Tabs\Tab::make('Variants')
                    ->schema([
                        Forms\Components\Repeater::make('variants')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('variant_label')
                                    ->label('Label')
                                    ->placeholder('e.g. Red / XL')
                                    ->required(),

                                Forms\Components\KeyValue::make('attribute_values')
                                    ->label('Attribute Values')
                                    ->keyLabel('Attribute Slug')
                                    ->valueLabel('Value')
                                    ->required(),

                                Forms\Components\TextInput::make('sku')
                                    ->label('SKU'),

                                Forms\Components\TextInput::make('barcode'),

                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->prefix('৳')
                                    ->placeholder('Uses product price if empty'),

                                Forms\Components\TextInput::make('sale_price')
                                    ->numeric()
                                    ->prefix('৳'),

                                Forms\Components\TextInput::make('weight')
                                    ->numeric()
                                    ->suffix('kg')
                                    ->placeholder('Uses product weight if empty'),

                                Forms\Components\SpatieMediaLibraryFileUpload::make('image')
                                    ->collection('image')
                                    ->image(),

                                Forms\Components\Toggle::make('is_active')
                                    ->default(true),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add Variant')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Tabs\Tab::make('SEO')
                    ->schema([
                        Forms\Components\TextInput::make('meta_title')
                            ->columnSpanFull(),
                        Forms\Components\Textarea::make('meta_description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('thumbnail')
                    ->collection('thumbnail')
                    ->size(50),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('category.name')
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('base_price')
                    ->money('BDT')
                    ->sortable(),

                Tables\Columns\TextColumn::make('sale_price')
                    ->money('BDT')
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('stock_quantity')
                    ->getStateUsing(fn ($record) => $record->stock_quantity)
                    ->color(fn ($record) => $record->is_low_stock ? 'warning' : null),

                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'gray' => 'draft',
                        'success' => 'active',
                        'warning' => 'inactive',
                        'danger' => 'archived',
                    ]),

                Tables\Columns\IconColumn::make('is_featured')
                    ->boolean()
                    ->label('Featured'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(array_combine(Product::STATUSES, array_map(fn ($s) => ucfirst($s), Product::STATUSES))),
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->options(fn () => Category::where('store_id', Store::current()->id)->pluck('name', 'id')),
                Tables\Filters\SelectFilter::make('brand_id')
                    ->label('Brand')
                    ->options(fn () => Brand::where('store_id', Store::current()->id)->pluck('name', 'id')),
                Tables\Filters\TernaryFilter::make('is_featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ReplicateAction::make()
                    ->label('Duplicate')
                    ->icon('heroicon-o-document-duplicate')
                    ->excludeAttributes(['slug', 'sku'])
                    ->beforeReplicaSaved(function (Product $replica) {
                        $replica->slug = Str::slug($replica->name).'-copy-'.Str::lower(Str::random(4));
                        $replica->sku = null;
                        $replica->status = 'draft';
                    })
                    ->after(function (Product $record, $replica) {
                        foreach (['thumbnail', 'images'] as $collection) {
                            $record->getMedia($collection)->each(
                                fn ($media) => $media->copy($replica, $collection)
                            );
                        }

                        foreach ($record->variants as $variant) {
                            $newVariant = $variant->replicate(['sku']);
                            $newVariant->product_id = $replica->id;
                            $newVariant->sku = null;
                            $newVariant->save();

                            $variant->getMedia('image')->each(
                                fn ($media) => $media->copy($newVariant, 'image')
                            );
                        }

                        Notification::make()
                            ->title('Product duplicated')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('manage_stock')
                    ->label('Manage Stock')
                    ->icon('heroicon-o-archive-box')
                    ->color('gray')
                    ->visible(fn (Product $record) => $record->track_inventory)
                    ->form(function (Product $record) {
                        $warehouses = Warehouse::where('store_id', $record->store_id)->where('is_active', true)->get();
                        $variants = $record->variants;

                        $targets = $variants->isNotEmpty()
                            ? $variants->flatMap(fn ($variant) => $warehouses->map(fn ($warehouse) => [$variant, $warehouse]))
                            : $warehouses->map(fn ($warehouse) => [null, $warehouse]);

                        $rows = $targets->map(function ($pair) use ($record) {
                            [$variant, $warehouse] = $pair;
                            $stockRow = WarehouseStock::where('warehouse_id', $warehouse->id)
                                ->where('product_id', $record->id)
                                ->where('variant_id', $variant?->id)
                                ->first();

                            return [
                                'warehouse_id' => $warehouse->id,
                                'variant_id' => $variant?->id,
                                'label' => $warehouse->name.($variant ? ' — '.$variant->variant_label : ''),
                                'quantity' => $stockRow->quantity ?? 0,
                                'reserved' => $stockRow->reserved_quantity ?? 0,
                                'reason' => '',
                            ];
                        })->values()->toArray();

                        return [
                            Forms\Components\Repeater::make('rows')
                                ->label('Stock by Warehouse')
                                ->schema([
                                    Forms\Components\Hidden::make('warehouse_id'),
                                    Forms\Components\Hidden::make('variant_id'),
                                    Forms\Components\Hidden::make('label'),
                                    Forms\Components\TextInput::make('quantity')
                                        ->label('On Hand')
                                        ->helperText('Physical count in this warehouse.')
                                        ->numeric()
                                        ->required(),
                                    Forms\Components\TextInput::make('reserved')
                                        ->label('Reserved')
                                        ->helperText('Held by unshipped orders — not editable here.')
                                        ->disabled()
                                        ->dehydrated(false),
                                    Forms\Components\TextInput::make('reason')
                                        ->label('Reason (optional)')
                                        ->placeholder('e.g. Physical recount'),
                                ])
                                ->itemLabel(fn (array $state): ?string => $state['label'] ?? null)
                                ->columns(3)
                                ->addable(false)
                                ->deletable(false)
                                ->default($rows),
                        ];
                    })
                    ->action(function (Product $record, array $data) {
                        $changed = 0;

                        foreach ($data['rows'] as $row) {
                            $stock = WarehouseStock::firstOrCreate(
                                ['warehouse_id' => $row['warehouse_id'], 'product_id' => $record->id, 'variant_id' => $row['variant_id']],
                                ['quantity' => 0, 'reserved_quantity' => 0]
                            );

                            $newQuantity = (int) $row['quantity'];
                            $delta = $newQuantity - $stock->quantity;

                            if ($delta === 0) {
                                continue;
                            }

                            $stock->update(['quantity' => $newQuantity]);

                            InventoryMovement::create([
                                'store_id' => $record->store_id,
                                'warehouse_id' => $row['warehouse_id'],
                                'product_id' => $record->id,
                                'variant_id' => $row['variant_id'],
                                'type' => 'adjustment',
                                'quantity_change' => $delta,
                                'quantity_after' => $newQuantity,
                                'reason' => $row['reason'] ?: null,
                                'created_by' => auth()->user()?->name ?? 'Admin',
                            ]);

                            $changed++;
                        }

                        Notification::make()
                            ->title($changed > 0 ? "Stock updated ({$changed} location(s))" : 'No changes made')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('report_damaged')
                    ->label('Report Damaged')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('danger')
                    ->visible(fn (Product $record) => $record->track_inventory)
                    ->form(function (Product $record) {
                        $warehouses = Warehouse::where('store_id', $record->store_id)->where('is_active', true)->get();
                        $variants = $record->variants;

                        $options = $variants->isNotEmpty()
                            ? $variants->flatMap(fn ($variant) => $warehouses->map(fn ($warehouse) => [
                                'value' => $warehouse->id.':'.$variant->id,
                                'label' => $warehouse->name.' — '.$variant->variant_label,
                            ]))
                            : $warehouses->map(fn ($warehouse) => [
                                'value' => $warehouse->id.':',
                                'label' => $warehouse->name,
                            ]);

                        return [
                            Forms\Components\Select::make('target')
                                ->label('Warehouse'.($variants->isNotEmpty() ? ' / Variant' : ''))
                                ->options($options->pluck('label', 'value'))
                                ->required(),
                            Forms\Components\TextInput::make('quantity')
                                ->numeric()
                                ->minValue(1)
                                ->required(),
                            Forms\Components\Textarea::make('reason')
                                ->label('Reason')
                                ->required()
                                ->rows(2)
                                ->placeholder('e.g. Water damage during storage'),
                        ];
                    })
                    ->action(function (Product $record, array $data) {
                        [$warehouseId, $variantId] = array_pad(explode(':', $data['target']), 2, null);
                        $variantId = $variantId ?: null;

                        $stock = WarehouseStock::firstOrCreate(
                            ['warehouse_id' => $warehouseId, 'product_id' => $record->id, 'variant_id' => $variantId],
                            ['quantity' => 0, 'reserved_quantity' => 0]
                        );

                        $qty = min((int) $data['quantity'], $stock->quantity);

                        if ($qty <= 0) {
                            Notification::make()->title('No on-hand stock to write off')->danger()->send();

                            return;
                        }

                        $stock->decrement('quantity', $qty);

                        InventoryMovement::create([
                            'store_id' => $record->store_id,
                            'warehouse_id' => $warehouseId,
                            'product_id' => $record->id,
                            'variant_id' => $variantId,
                            'type' => 'damaged',
                            'quantity_change' => -$qty,
                            'quantity_after' => $stock->fresh()->quantity,
                            'reason' => $data['reason'],
                            'created_by' => auth()->user()?->name ?? 'Admin',
                        ]);

                        Notification::make()->title("Reported {$qty} unit(s) as damaged")->success()->send();
                    }),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Set Active')
                        ->icon('heroicon-o-check-circle')
                        ->action(fn ($records) => $records->each->update(['status' => 'active'])),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
