<?php

namespace App\Filament\Widgets;

use App\Models\Backup;
use Filament\Widgets\ChartWidget;

class BackupSizeChart extends ChartWidget
{
    protected static ?string $heading = 'Chart Size';

    protected function getData(): array
    {
        $data = Backup::orderBy('created_at')
            ->selectRaw('DATE(created_at) as date, SUM(final_size) as size')
            ->groupBy('date')
            ->get();

        return [
            'datasets' => [
                [
                    'label' => 'Total Size (MB)',
                    'data' => $data->pluck('size')->map(fn($v) => round($v / 1024 / 1024, 2)),
                ],
            ],
            'labels' => $data->pluck('date'),
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
