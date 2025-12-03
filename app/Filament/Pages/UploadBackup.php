<?php

namespace App\Filament\Pages;

use App\Models\Backup;
use Filament\Pages\Page;

class UploadBackup extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $navigationLabel = 'Upload Backup';
    protected static string $view = 'filament.pages.upload-backup';

    public $backups;
    public function mount()
    {
        $this->backups = Backup::orderBy('created_at', 'desc')->get();
    }

    protected function getViewData(): array
    {
        return [
            'backups' => $this->backups,
        ];
    }
}
