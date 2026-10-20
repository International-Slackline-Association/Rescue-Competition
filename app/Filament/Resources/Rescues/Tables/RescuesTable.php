<?php

namespace App\Filament\Resources\Rescues\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class RescuesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->with('creator')->withCount('participants'))
            ->defaultSort('created_at', 'desc')
            ->columns([
                ImageColumn::make('image_path')
                    ->label('Proof')
                    ->disk('public'),
                TextColumn::make('happened_on')
                    ->date()
                    ->sortable(),
                TextColumn::make('location')
                    ->searchable(),
                TextColumn::make('participants_count')
                    ->label('People')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Submitted by')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->recordActions([
                ViewAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
