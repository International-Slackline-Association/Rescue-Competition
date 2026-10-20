<?php

namespace App\Filament\Resources\Rescues;

use App\Filament\Resources\Rescues\Pages\ListRescues;
use App\Filament\Resources\Rescues\Pages\ViewRescue;
use App\Filament\Resources\Rescues\Schemas\RescueInfolist;
use App\Filament\Resources\Rescues\Tables\RescuesTable;
use App\Models\Rescue;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class RescueResource extends Resource
{
    protected static ?string $model = Rescue::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLifebuoy;

    public static function infolist(Schema $schema): Schema
    {
        return RescueInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RescuesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRescues::route('/'),
            'view' => ViewRescue::route('/{record}'),
        ];
    }
}
