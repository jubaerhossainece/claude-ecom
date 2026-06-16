<?php

namespace App\Filament\Resources\OrderResource\Pages;

use App\Filament\Resources\OrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOrder extends EditRecord
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make()];
    }

    protected function afterSave(): void
    {
        $this->record->addStatusHistory(
            $this->record->status,
            'Updated by admin',
            auth()->user()->name
        );
    }
}
