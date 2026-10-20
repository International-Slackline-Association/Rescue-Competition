<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn ($query) => $query->withCount('rescues'))
            ->defaultSort('name')
            ->columns([
                ImageColumn::make('avatar_url')
                    ->label('')
                    ->circular(),
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('isa_id')
                    ->label('ISA ID')
                    ->searchable(),
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('rescues_count')
                    ->label('Rescues')
                    ->sortable(),
                IconColumn::make('cognito_sub')
                    ->label('Logged in')
                    ->boolean()
                    ->getStateUsing(fn ($record) => filled($record->cognito_sub)),
                IconColumn::make('is_admin')
                    ->label('Admin')
                    ->boolean(),
            ])
            ->filters([
                TernaryFilter::make('is_admin')->label('Admin'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
