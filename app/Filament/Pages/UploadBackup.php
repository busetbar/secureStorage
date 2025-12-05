<?php

namespace App\Filament\Pages;

use App\Models\Backup;
use Filament\Pages\Page;

class UploadBackup extends Page
{
    // ⛔ Tidak tampil di menu
    protected static ?string $navigationIcon = null;
    protected static bool $shouldRegisterNavigation = false;

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
