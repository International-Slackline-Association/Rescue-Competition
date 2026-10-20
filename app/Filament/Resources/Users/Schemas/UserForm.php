<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->email()
                    ->unique(ignoreRecord: true),
                TextInput::make('isa_id')
                    ->label('ISA ID')
                    ->disabled(),
                Toggle::make('is_admin')
                    ->label('Admin'),
            ]);
    }
}
