<?php

namespace App\Filament\Resources\Rescues\Pages;

use App\Filament\Resources\Rescues\RescueResource;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\ViewRecord;

class ViewRescue extends ViewRecord
{
    protected static string $resource = RescueResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('public')
                ->label('Open on site')
                ->url(fn () => route('rescues.show', $this->record), shouldOpenInNewTab: true),
            DeleteAction::make(),
        ];
    }
}
