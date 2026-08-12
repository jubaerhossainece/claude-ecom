<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AttributeResource\Pages;
use App\Models\Attribute;
use App\Models\Store;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class AttributeResource extends Resource
{
    protected static ?string $model = Attribute::class;
    protected static ?string $navigationIcon = 'heroicon-o-tag';
    protected static ?string $navigationGroup = 'Catalog';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Hidden::make('store_id')
                ->default(fn () => Store::current()->id),

            Forms\Components\TextInput::make('name')
                ->required()
                ->live(onBlur: true)
                ->afterStateUpdated(fn ($state, Forms\Set $set) => $set('slug', Str::slug($state))),

            Forms\Components\TextInput::make('slug')
                ->required()
                ->unique(ignoreRecord: true),

            Forms\Components\Select::make('type')
                ->options(Attribute::TYPES)
                ->required()
                ->default('text')
                ->live(),

            Forms\Components\TextInput::make('unit')
                ->placeholder('kg, cm, pcs...')
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['text', 'number'])),

            Forms\Components\Repeater::make('options')
                ->schema([
                    Forms\Components\TextInput::make('label')->required(),
                    Forms\Components\TextInput::make('value')->required(),
                ])
                ->columns(2)
                ->visible(fn (Forms\Get $get) => in_array($get('type'), ['select', 'multiselect']))
                ->addActionLabel('Add Option'),

            Forms\Components\Toggle::make('is_filterable')
                ->label('Use as filter on storefront')
                ->default(false),

            Forms\Components\Toggle::make('is_variant')
                ->label('Use to generate product variants')
                ->default(false),

            Forms\Components\Toggle::make('is_required')
                ->label('Required when adding products')
                ->default(false),

            Forms\Components\TextInput::make('sort_order')
                ->numeric()
                ->default(0),
        ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'gray' => 'text',
                        'info' => 'number',
                        'success' => 'select',
                        'warning' => 'multiselect',
                        'primary' => 'color',
                    ]),
                Tables\Columns\TextColumn::make('unit')->placeholder('—'),
                Tables\Columns\IconColumn::make('is_filterable')->boolean()->label('Filterable'),
                Tables\Columns\IconColumn::make('is_variant')->boolean()->label('Variant'),
                Tables\Columns\TextColumn::make('categories_count')
                    ->counts('categories')
                    ->label('Categories'),
                Tables\Columns\TextColumn::make('sort_order')->sortable(),
            ])
            ->defaultSort('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options(Attribute::TYPES),
                Tables\Filters\TernaryFilter::make('is_filterable'),
                Tables\Filters\TernaryFilter::make('is_variant'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttributes::route('/'),
        ];
    }
}
