<?php

namespace App\Filament\Pages;

use App\Models\Backup;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Http;

class BackupDetails extends Page
{

    protected static string $view = 'filament.pages.backup-details';

    // route custom tanpa navigation
    protected static ?string $navigationIcon = null;
    protected static bool $shouldRegisterNavigation = false;

    public Backup $backup;

    public function mount($id)
    {
        $this->backup = Backup::findOrFail($id);
    }
    public function measureDecryptTime()
    {
        $url = "http://192.168.200.211:9090/integrity?path={$this->backup->path}";

        $response = Http::timeout(60)->get($url);

        if (! $response->ok()) {
            $this->dispatch('notify', type: 'danger', message: 'Failed to calculate decrypt time.');
            return;
        }

        $time = $response->json()['time_ms'];

        $this->backup->duration_decrypt_ms = $time;
        $this->backup->save();

        $this->dispatch('notify', type: 'success', message: "Decrypt time measured: {$time} ms");
    }

    public static function canAccess(): bool
    {
        return true; // jika tidak ingin ada menu navigasi
    }

    protected function getViewData(): array
    {
        return [
            'backup' => $this->backup
        ];
    }
}
