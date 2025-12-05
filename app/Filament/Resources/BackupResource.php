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
use Illuminate\Support\Facades\Http;   // ← WAJIB TAMBAHKAN INI
use Illuminate\Support\Facades\Log;

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
            ->poll('5s')
            ->columns([

                TextColumn::make('name')->sortable()->searchable(),

                TextColumn::make('original_filename')
                    ->label('Original Filename')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('original_size')
                    ->label('Original Size')
                    ->formatStateUsing(fn ($state) =>
                        number_format($state / 1024 / 1024, 2) . ' MB'
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
                
                // ==============================
                // DOWNLOAD DECRYPTED FILE
                // ==============================
                Tables\Actions\Action::make('download')
                    ->label('Download')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn ($record) =>
                        "http://127.0.0.1:9090/download/decrypted?path={$record->path}&filename={$record->original_filename}"
                    )
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->status === 'completed'),

                // ==============================
                // DELETE BACKUP (Laravel + Go Worker)
                // ==============================
                Tables\Actions\Action::make('delete_backup')
                    ->label('Delete')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(function ($record) {

                        if (! $record->path) {
                            throw new \Exception('Backup path empty.');
                        }

                        $cleanPath = trim($record->path);   // ← FIX TERBESAR
                        $encodedPath = str_replace(' ', '%20', $record->path);
                        $url = "http://127.0.0.1:9090/delete?path={$encodedPath}";

                        Log::info("Deleting MinIO file", [
                            'id' => $record->id,
                            'raw_path' => $record->path,
                            'encoded_path' => $encodedPath,
                            'url' => $url,
                        ]);

                        $response = Http::get($url);

                        Log::info("Worker delete response", [
                            'status' => $response->status(),
                            'body' => $response->body(),
                        ]);

                        if (! $response->ok()) {
                            throw new \Exception("Failed to delete backup file in worker (status {$response->status()})");
                        }

                        $record->delete();
                    })
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
