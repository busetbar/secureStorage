<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BackupResource\Pages;
use App\Models\Backup;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\BadgeColumn;
use Filament\Tables\Columns\TextColumn;

class BackupResource extends Resource
{
    protected static ?string $model = Backup::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Backups';

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('5s') // Auto refresh setiap 5 detik
            ->columns([

                TextColumn::make('name')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('original_filename')
                    ->label('Original Filename')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('original_size')
                    ->label('Original Size')
                    ->formatStateUsing(fn ($state) =>
                        number_format($state / 1024 / 1024, 2).' MB'
                    ),

                BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'uploading',
                        'success' => 'completed',
                        'danger'  => 'failed',
                    ])
                    ->label('Status'),

                TextColumn::make('created_at')
                    ->dateTime('d M Y H:i')
                    ->label('Uploaded'),
            ])
            ->actions([
                
                // DOWNLOAD DECRYPTED FILE
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) =>
                        "http://127.0.0.1:9090/download/decrypted?path={$record->path}&filename={$record->original_filename}"
                    )
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->status === 'completed'),

                Tables\Actions\DeleteAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBackups::route('/'),
        ];
    }
}
