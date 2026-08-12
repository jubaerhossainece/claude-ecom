<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReturnRequestResource\Pages;
use App\Models\ReturnRequest;
use Filament\Forms;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReturnRequestResource extends Resource
{
    protected static ?string $model = ReturnRequest::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrow-uturn-left';
    protected static ?string $navigationGroup = 'Sales';
    protected static ?int $navigationSort = 2;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('Request Details')
                ->schema([
                    Infolists\Components\TextEntry::make('order.order_number')->label('Order'),
                    Infolists\Components\TextEntry::make('customer.name')->label('Customer'),
                    Infolists\Components\TextEntry::make('orderItem.product_name')->label('Product'),
                    Infolists\Components\TextEntry::make('type')->formatStateUsing(fn ($state) => ReturnRequest::TYPES[$state] ?? $state),
                    Infolists\Components\TextEntry::make('reason')->formatStateUsing(fn ($state) => ReturnRequest::REASONS[$state] ?? $state),
                    Infolists\Components\TextEntry::make('status')->badge(),
                    Infolists\Components\TextEntry::make('description')->placeholder('—')->columnSpanFull(),
                    Infolists\Components\SpatieMediaLibraryImageEntry::make('photos')->collection('photos')->columnSpanFull(),
                    Infolists\Components\TextEntry::make('admin_notes')->placeholder('—')->columnSpanFull(),
                ])->columns(3),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.order_number')->label('Order')->searchable(),
                Tables\Columns\TextColumn::make('customer.name')->label('Customer')->searchable(),
                Tables\Columns\TextColumn::make('orderItem.product_name')->label('Product'),
                Tables\Columns\BadgeColumn::make('type')
                    ->formatStateUsing(fn ($state) => ReturnRequest::TYPES[$state] ?? $state)
                    ->colors(['gray' => 'return', 'info' => 'exchange']),
                Tables\Columns\TextColumn::make('reason')->formatStateUsing(fn ($state) => ReturnRequest::REASONS[$state] ?? $state),
                Tables\Columns\BadgeColumn::make('status')
                    ->formatStateUsing(fn ($state) => ReturnRequest::STATUSES[$state] ?? $state)
                    ->colors([
                        'warning' => 'pending',
                        'success' => fn ($state) => in_array($state, ['approved', 'completed']),
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->dateTime('d M Y H:i')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')->options(ReturnRequest::STATUSES),
                Tables\Filters\SelectFilter::make('type')->options(ReturnRequest::TYPES),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (ReturnRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')->label('Note to customer (optional)')->rows(2),
                    ])
                    ->action(function (ReturnRequest $record, array $data) {
                        $record->update(['status' => 'approved', 'admin_notes' => $data['admin_notes'] ?? $record->admin_notes]);
                        Notification::make()->title('Request approved')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->visible(fn (ReturnRequest $record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')->label('Reason for rejection')->required()->rows(2),
                    ])
                    ->action(function (ReturnRequest $record, array $data) {
                        $record->update(['status' => 'rejected', 'admin_notes' => $data['admin_notes']]);
                        Notification::make()->title('Request rejected')->success()->send();
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Mark Completed')
                    ->icon('heroicon-o-check-circle')
                    ->color('gray')
                    ->visible(fn (ReturnRequest $record) => $record->status === 'approved')
                    ->action(function (ReturnRequest $record) {
                        $record->update(['status' => 'completed']);
                        Notification::make()->title('Marked completed')->success()->send();
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListReturnRequests::route('/'),
            'view' => Pages\ViewReturnRequest::route('/{record}'),
        ];
    }
}
