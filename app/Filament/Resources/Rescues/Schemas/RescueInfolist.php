<?php

namespace App\Filament\Resources\Rescues\Schemas;

use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class RescueInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                ImageEntry::make('image_path')
                    ->label('Proof')
                    ->disk('public')
                    ->imageHeight(320)
                    ->columnSpanFull(),
                TextEntry::make('happened_on')
                    ->date(),
                TextEntry::make('location'),
                TextEntry::make('participants.name')
                    ->label('Involved')
                    ->badge(),
                TextEntry::make('creator.name')
                    ->label('Submitted by'),
                TextEntry::make('description')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('created_at')
                    ->dateTime(),
            ]);
    }
}
