<?php
namespace App\Filament\Resources;

use App\Filament\Resources\VisitorResource\Pages;
use App\Models\Visitor;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;

class VisitorResource extends Resource
{
    protected static ?string $model = Visitor::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('Visitor Details')
                    ->description('Basic information about the visitor/pass holder.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('phone')
                            ->tel()
                            ->maxLength(255)
                            ->nullable(),
                        Textarea::make('purpose')
                            ->rows(3)
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->nullable(),
                    ]),

                Section::make('Pass Configuration')
                    ->description('Settings related to the QR code and pass validity.')
                    ->columns(2)
                    ->schema([
                        TextInput::make('qr_code_data')
                            ->label('QR Code Content')
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255)
                            ->helperText('This unique string will be embedded in the QR code. Ensure it\'s unique for each pass.'),
                        DateTimePicker::make('valid_from')
                            ->label('Valid From')
                            ->nullable(),
                        DateTimePicker::make('valid_until')
                            ->label('Valid Until')
                            ->nullable(),
                        Toggle::make('is_active')
                            ->label('Is Active')
                            ->helperText('Deactivate to invalidate the pass immediately, regardless of date ranges.')
                            ->inline(false)
                            ->default(true)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('phone')
                    ->searchable(),
                TextColumn::make('qr_code_data')
                    ->label('QR Data')
                    ->searchable(),
                TextColumn::make('valid_from')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('valid_until')
                    ->dateTime()
                    ->sortable(),
                ToggleColumn::make('is_active')
                    ->label('Active'),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->trueLabel('Active')
                    ->falseLabel('Inactive')
                    ->nullable()
                    ->placeholder('All'),
                Tables\Filters\Filter::make('valid_status')
                    ->label('Validity')
                    ->query(function (Builder $query): Builder {
                        return $query
                            ->when(request()->get('filters')['valid_status'] === 'valid_now', function ($query) {
                                $query->where('is_active', true)
                                      ->where(function ($q) {
                                          $q->whereNull('valid_from')->orWhere('valid_from', '<=', now());
                                      })
                                      ->where(function ($q) {
                                          $q->whereNull('valid_until')->orWhere('valid_until', '>=', now());
                                      });
                            })
                            ->when(request()->get('filters')['valid_status'] === 'expired', function ($query) {
                                $query->where('is_active', true) // Only active passes can expire
                                      ->whereNotNull('valid_until')
                                      ->where('valid_until', '<', now());
                            })
                            ->when(request()->get('filters')['valid_status'] === 'not_yet_valid', function ($query) {
                                $query->where('is_active', true) // Only active passes can be not yet valid
                                      ->whereNotNull('valid_from')
                                      ->where('valid_from', '>', now());
                            });
                    })
                    ->options([
                        'valid_now' => 'Valid Now',
                        'expired' => 'Expired',
                        'not_yet_valid' => 'Not Yet Valid',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\ViewAction::make(),
                // Optionally add a QR code viewer/generator action here
                Tables\Actions\Action::make('view_qr')
                    ->label('View QR')
                    ->icon('heroicon-o-qr-code')
                    ->modalSubmitAction(false) // No need for submit button if just displaying
                    ->modalCancelActionLabel('Close')
                    ->modalContent(fn (Visitor $record) => view('filament.forms.components.qr-code-viewer', ['qrData' => $record->qr_code_data]))
                    ->extraAttributes([ // Ensure the modal size is appropriate
                        'x-data' => '{}',
                        'x-on:click.stop.prevent' => '$dispatch(\'open-modal\', { id: \'filament-actions::view-qr\' })'
                    ]),

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
            // Add a relation for ScanLogs to show recent scans for a visitor
            // VisitorResource\RelationManagers\ScanLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVisitors::route('/'),
            'create' => Pages\CreateVisitor::route('/create'),
            'edit' => Pages\EditVisitor::route('/{record}/edit'),
        ];
    }
}