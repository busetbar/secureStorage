<?php

namespace App\Filament\Resources\BackupResource\Pages;

use App\Filament\Resources\BackupResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListBackups extends ListRecords
{
    protected static string $resource = BackupResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //Actions\CreateAction::make(),
            Actions\Action::make('upload')
            ->label('Upload Backup')
            ->icon('heroicon-o-arrow-up-tray')
            ->color('primary')
            ->url('/admin/upload-backup'), // ke Filament Page custom
        ];
    }
}
