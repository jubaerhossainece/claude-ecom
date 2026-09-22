<?php

namespace App\Filament\Resources\BrandResource\RelationManagers;

use App\Filament\Resources\ProductResource;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    public function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\SpatieMediaLibraryImageColumn::make('thumbnail')
                    ->collection('thumbnail')
                    ->size(40),
                Tables\Columns\TextColumn::make('name')->searchable(),
                Tables\Columns\TextColumn::make('sku')->placeholder('—'),
                Tables\Columns\BadgeColumn::make('status'),
                Tables\Columns\TextColumn::make('base_price')->money('BDT'),
                Tables\Columns\TextColumn::make('stock_quantity')->label('Available'),
            ])
            ->headerActions([])
            ->actions([
                Tables\Actions\Action::make('edit')
                    ->label('Edit')
                    ->icon('heroicon-o-pencil-square')
                    ->url(fn ($record) => ProductResource::getUrl('edit', ['record' => $record])),
            ])
            ->bulkActions([]);
    }

    protected function canCreate(): bool
    {
        return false;
    }
}
