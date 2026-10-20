<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    /**
     * is_admin is deliberately not mass assignable; only this admin form may set it.
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $record->forceFill($data)->save();

        return $record;
    }

    protected function getHeaderActions(): array
    {
        return [
            // Bearer token for the /mcp server. Admins only get one for themselves; a new one revokes the old.
            Action::make('mcpToken')
                ->label('New MCP token')
                ->visible(fn () => $this->record->is(Auth::user()) && $this->record->is_admin)
                ->requiresConfirmation()
                ->modalDescription('This revokes your current MCP token. The new one is shown once.')
                ->action(function () {
                    $this->record->tokens()->where('name', 'mcp')->delete();

                    Notification::make()
                        ->title('Your MCP token')
                        ->body($this->record->createToken('mcp')->plainTextToken)
                        ->persistent()
                        ->success()
                        ->send();
                }),
        ];
    }
}
