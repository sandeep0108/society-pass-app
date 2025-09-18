<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScanLogResource\Pages;
use App\Filament\Resources\ScanLogResource\RelationManagers;
use App\Models\ScanLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScanLogResource extends Resource
{
    protected static ?string $model = ScanLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('visitor_id')
                    ->numeric(),
                Forms\Components\TextInput::make('scanned_data')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_valid_pass')
                    ->required(),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('scanned_by_user_id')
                    ->numeric(),
                Forms\Components\TextInput::make('scanner_ip')
                    ->maxLength(45),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('visitor_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scanned_data')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_valid_pass')
                    ->boolean(),
                Tables\Columns\TextColumn::make('scanned_by_user_id')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('scanner_ip')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListScanLogs::route('/'),
            'create' => Pages\CreateScanLog::route('/create'),
            'edit' => Pages\EditScanLog::route('/{record}/edit'),
        ];
    }
}
