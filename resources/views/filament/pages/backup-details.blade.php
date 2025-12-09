<x-filament-panels::page>

    {{-- Heading --}}
    <div class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Backup Details</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
            Detailed information about your encrypted backup file.
        </p>
    </div>

    {{-- SECTION: FILE INFORMATION --}}
    <x-filament::section>
        <x-slot name="heading">File Information</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm">

            {{-- LEFT --}}
            <div class="space-y-4">

                <x-filament::grid class="gap-1">
                    <span class="text-gray-500 dark:text-gray-400">Name</span>
                    <span class="font-medium text-gray-900 dark:text-gray-200">
                        {{ $backup->name }}
                    </span>
                </x-filament::grid>

                <x-filament::grid class="gap-1">
                    <span class="text-gray-500 dark:text-gray-400">Original Filename</span>
                    <span class="font-medium text-gray-900 dark:text-gray-200">
                        {{ $backup->original_filename }}
                    </span>
                </x-filament::grid>

                <x-filament::grid class="gap-1">
                    <span class="text-gray-500 dark:text-gray-400">Encrypted Filename</span>
                    <span class="font-medium text-gray-900 dark:text-gray-200">
                        {{ $backup->stored_filename }}
                    </span>
                </x-filament::grid>

                <x-filament::grid class="gap-1">
                    <span class="text-gray-500 dark:text-gray-400">Status</span>
                    <x-filament::badge 
                        color="{{ $backup->status === 'completed' ? 'success' : ($backup->status === 'uploading' ? 'warning' : 'danger') }}"
                        class="w-fit"
                    >
                        {{ ucfirst($backup->status) }}
                    </x-filament::badge>
                </x-filament::grid>

            </div>

            {{-- RIGHT --}}
            <div class="space-y-4">

                <x-filament::grid class="gap-1">
                    <span class="text-gray-500 dark:text-gray-400">Original Size</span>
                    <span class="font-medium text-gray-900 dark:text-gray-200">
                        {{ number_format($backup->original_size / 1024 / 1024, 2) }} MB
                    </span>
                </x-filament::grid>

                <x-filament::grid class="gap-1">
                    <span class="text-gray-500 dark:text-gray-400">Encrypted Size</span>
                    <span class="font-medium text-gray-900 dark:text-gray-200">
                        {{ number_format($backup->final_size / 1024 / 1024, 2) }} MB
                    </span>
                </x-filament::grid>

                {{-- COMPRESSION --}}
                @php
                    $percentage = ($backup->final_size && $backup->original_size)
                        ? ($backup->final_size / $backup->original_size) * 100
                        : null;
                @endphp

                <div>
                    <span class="text-gray-500 dark:text-gray-400">Compression Ratio</span>

                    <div class="font-semibold text-gray-900 dark:text-gray-200">
                        {{ $percentage ? number_format($percentage, 2) . '%' : '-' }}
                    </div>

                    @if ($percentage)
                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">

                            @if ($percentage < 100)
                                <span class="text-success-600 font-medium">
                                    {{ 100 - number_format($percentage, 2) }}% smaller
                                </span>
                                than original.
                            @elseif ($percentage == 100)
                                No size reduction. File remained the same.
                            @else
                                <span class="text-warning-600 font-medium">
                                    {{ number_format($percentage - 100, 2) }}% larger
                                </span>
                                — typical for already-compressed file types (JPEG, MP4, ZIP).
                            @endif

                        </div>
                    @endif
                </div>

                {{-- ENCRYPT DURATION --}}
                <x-filament::grid class="gap-1">
                    <span class="text-gray-500 dark:text-gray-400">Encrypt Duration</span>
                    <span class="font-medium text-gray-900 dark:text-gray-200">
                        {{ $backup->duration_encrypt_ms ? $backup->duration_encrypt_ms . ' ms' : '-' }}
                    </span>
                </x-filament::grid>

                {{-- DECRYPT DURATION --}}
                <x-filament::grid class="gap-1">
                    <span class="text-gray-500 dark:text-gray-400">Decrypt Duration</span>
                    <span class="font-medium text-gray-900 dark:text-gray-200">
                        {{ $backup->duration_decrypt_ms ? $backup->duration_decrypt_ms . ' ms' : '-' }}
                    </span>
                </x-filament::grid>

            </div>

        </div>
    </x-filament::section>

    <x-filament::section>
    <x-slot name="heading">Integrity Information</x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm">

        {{-- ORIGINAL HASH --}}
        <div class="space-y-4">
            <x-filament::grid class="gap-1">
                <span class="text-gray-500 dark:text-gray-400">Original SHA Integrity File</span>
                <span class="font-mono text-xs break-all text-gray-900 dark:text-gray-200">
                    {{ $backup->original_sha256 }}
                </span>
            </x-filament::grid>

            <x-filament::grid class="gap-1">
                <span class="text-gray-500 dark:text-gray-400">After Decrypt SHA Integrity File</span>
                <span class="font-mono text-xs break-all text-gray-900 dark:text-gray-200">
                    {{ $backup->after_sha256 ?? '-' }}
                </span>
            </x-filament::grid>
        </div>

        {{-- INTEGRITY STATUS --}}
        <div class="space-y-4">

            <x-filament::grid class="gap-1">
                <span class="text-gray-500 dark:text-gray-400">Integrity Status</span>

                @if ($backup->after_sha256)
                    <x-filament::badge 
                        color="{{ $backup->integrity_passed ? 'success' : 'danger' }}"
                        class="w-fit"
                    >
                        {{ $backup->integrity_passed ? 'PASSED' : 'FAILED' }}
                    </x-filament::badge>
                @else
                    <x-filament::badge color="gray" class="w-fit">
                        Not Checked
                    </x-filament::badge>
                @endif
            </x-filament::grid>

            {{-- DECRYPT TIME --}}
            <x-filament::grid class="gap-1">
                <span class="text-gray-500 dark:text-gray-400">Decryption / Integrity Duration</span>
                <span class="font-medium text-gray-900 dark:text-gray-200">
                    {{ $backup->duration_decrypt_ms ? $backup->duration_decrypt_ms.' ms' : '-' }}
                </span>
            </x-filament::grid>
        </div>

    </div>
</x-filament::section>

    {{-- ACTION --}}
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
