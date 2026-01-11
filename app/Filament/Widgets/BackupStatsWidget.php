<?php

namespace App\Filament\Widgets;

use App\Models\Backup;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class BackupStatsWidget extends BaseWidget
{
    protected function getStats(): array
    {
        $totalBackups = Backup::count();
        $totalSize = Backup::sum('final_size');
        $avgEncrypt = Backup::avg('duration_encrypt_ms');
        $avgDecrypt = Backup::avg('duration_decrypt_ms');

        return [
            Stat::make('Total Backups', $totalBackups),

            Stat::make('Total Encrypted Size', number_format($totalSize / 1024 / 1024, 2) . ' MB'),

            Stat::make('Avg Encrypt Time', $avgEncrypt ? number_format($avgEncrypt) . ' ms' : 'N/A')
                ->color('primary'),

            Stat::make('Avg Decrypt Time', $avgDecrypt ? number_format($avgDecrypt) . ' ms' : 'N/A')
                ->color('success'),
        ];
    }

    
}
