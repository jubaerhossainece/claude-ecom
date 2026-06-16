<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProductResource\Pages;
use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
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
                            ->afterStateUpdated(fn ($state, Forms\Set $set) =>
                                $set('slug', Str::slug($state)))
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
                                    $cat->id => ($cat->parent ? $cat->parent->name . ' › ' : '') . $cat->name,
                                ]))
                            ->searchable()
                            ->required(),

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

                        Forms\Components\TextInput::make('stock_quantity')
                            ->numeric()
                            ->default(0)
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

                                Forms\Components\TextInput::make('price')
                                    ->numeric()
                                    ->prefix('৳')
                                    ->placeholder('Uses product price if empty'),

                                Forms\Components\TextInput::make('sale_price')
                                    ->numeric()
                                    ->prefix('৳'),

                                Forms\Components\TextInput::make('stock_quantity')
                                    ->numeric()
                                    ->default(0),

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
                    ->sortable()
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
                Tables\Filters\TernaryFilter::make('is_featured'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
