<?php

namespace App\Filament\Resources\BackupResource\Pages;

use App\Filament\Resources\BackupResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateBackup extends CreateRecord
{
    protected static string $resource = BackupResource::class;

    
    protected static string $view = 'filament.pages.upload-backup';

    public $name;
    public $file;
}
