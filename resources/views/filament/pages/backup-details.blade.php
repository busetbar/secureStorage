<x-filament-panels::page>
    <div class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Backup Details</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
            Detailed information about your encrypted backup file.
        </p>
    </div>

    <x-filament::section>
        <x-slot name="heading">
            File Information
        </x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

            {{-- Left Column --}}
            <div class="space-y-3">
                <div>
                    <span class="font-medium text-gray-600">Name:</span>
                    <div class="text-gray-900 dark:text-gray-200">{{ $backup->name }}</div>
                </div>

                <div>
                    <span class="font-medium text-gray-600">Original Filename:</span>
                    <div class="text-gray-900 dark:text-gray-200">{{ $backup->original_filename }}</div>
                </div>

                <div>
                    <span class="font-medium text-gray-600">Encrypted Filename:</span>
                    <div class="text-gray-900 dark:text-gray-200">{{ $backup->stored_filename }}</div>
                </div>

                <div>
                    <span class="font-medium text-gray-600">Status:</span>
                    <div>
                        <x-filament::badge 
                            color="{{ $backup->status === 'completed' ? 'success' : ($backup->status === 'uploading' ? 'warning' : 'danger') }}"
                        >
                            {{ ucfirst($backup->status) }}
                        </x-filament::badge>
                    </div>
                </div>
            </div>

            {{-- Right Column --}}
            <div class="space-y-3">
                <div>
                    <span class="font-medium text-gray-600">Original Size:</span>
                    <div class="text-gray-900 dark:text-gray-200">
                        {{ number_format($backup->original_size / 1024 / 1024, 2) }} MB
                    </div>
                </div>

                <div>
                    <span class="font-medium text-gray-600">Encrypted Size:</span>
                    <div class="text-gray-900 dark:text-gray-200">
                        {{ number_format($backup->final_size / 1024 / 1024, 2) }} MB
                    </div>
                </div>

<div>
    <span class="font-medium text-gray-600 dark:text-gray-400">Compression:</span>

    @php
        $percentage = ($backup->final_size && $backup->original_size)
            ? ($backup->final_size / $backup->original_size) * 100
            : null;
    @endphp

    {{-- Nilai Persentase --}}
    <div class="text-gray-900 dark:text-gray-200 font-semibold">
        {{ $percentage ? number_format($percentage, 2) . '%' : '-' }}
    </div>

    {{-- Penjelasan --}}
    @if ($percentage)
        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">

            @if ($percentage < 100)
                File berhasil diperkecil menjadi <b>{{ 100 - number_format($percentage, 2) }}%</b>
                dari ukuran asli.
            @elseif ($percentage == 100)
                Ukuran file tetap sama setelah kompresi.
            @else
                Ukuran file meningkat sebesar
                <b>{{ number_format($percentage - 100, 2) }}%</b>
                dibanding ukuran asli — biasanya terjadi pada file yang sudah terkompresi
                (ZIP, JPEG, MP4, PDF).
            @endif

        </div>
    @endif
</div>

                <div>
                    <span class="font-medium text-gray-600">Encrypt Duration:</span>
                    <div class="text-gray-900 dark:text-gray-200">
                        {{ $backup->duration_encrypt_ms ? $backup->duration_encrypt_ms.' ms' : 'Not available' }}
                    </div>
                </div>

                <div>
                    <span class="font-medium text-gray-600">Decrypt Duration:</span>
                    <div class="text-gray-900 dark:text-gray-200">
                        {{ $backup->duration_decrypt_ms ? $backup->duration_decrypt_ms.' ms' : 'Not measured yet' }}
                    </div>
                </div>
            </div>

        </div>
    </x-filament::section>

    <div class="mt-6 flex justify-end">
        <x-filament::button
            wire:click="measureDecryptTime"
            icon="heroicon-o-clock"
            color="primary"
        >
            Measure Decrypt Time
        </x-filament::button>
    </div>

</x-filament-panels::page>
