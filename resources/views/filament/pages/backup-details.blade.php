<x-filament-panels::page>

@php
    $percentage = ($backup->final_size && $backup->original_size)
        ? ($backup->final_size / $backup->original_size) * 100
        : null;

    $msToSecDetail = fn ($ms) =>
        $ms !== null
            ? number_format($ms / 1000, 2) . ' s (' . number_format($ms) . ' ms)'
            : '-';
@endphp

<div wire:poll.2s>

    {{-- Heading --}}
    <div class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">File Information</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
            Detailed information about your encrypted backup file.
        </p>
    </div>

    {{-- ================= BASIC FILE INFORMATION ================= --}}
    <x-filament::section>
        <x-slot name="heading">Basic File Information</x-slot>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-4 text-sm">
            <div>
                <dt class="text-gray-500">Name</dt>
                <dd class="font-medium">{{ $backup->name }}</dd>
            </div>

            <div>
                <dt class="text-gray-500">Status</dt>
                <dd>
                    <x-filament::badge
                        color="{{ $backup->status === 'completed'
                            ? 'success'
                            : ($backup->status === 'uploading' ? 'warning' : 'danger') }}">
                        {{ ucfirst($backup->status) }}
                    </x-filament::badge>
                </dd>
            </div>

            <div>
                <dt class="text-gray-500">Original Filename</dt>
                <dd class="font-medium">{{ $backup->original_filename }}</dd>
            </div>

            <div>
                <dt class="text-gray-500">Encrypted Filename</dt>
                <dd class="font-medium">{{ $backup->stored_filename }}</dd>
            </div>
        </dl>
    </x-filament::section>

    {{-- ================= SIZE & COMPRESSION ================= --}}
    <x-filament::section>
        <x-slot name="heading">Size & Compression</x-slot>

        <dl class="grid grid-cols-1 md:grid-cols-3 gap-6 text-sm">
            <div>
                <dt class="text-gray-500">Original Size</dt>
                <dd class="font-semibold">
                    {{ number_format($backup->original_size / 1024 / 1024, 2) }} MB
                </dd>
            </div>

            <div>
                <dt class="text-gray-500">Encrypted Size</dt>
                <dd class="font-semibold">
                    {{ number_format($backup->final_size / 1024 / 1024, 2) }} MB
                </dd>
            </div>

            <div>
                <dt class="text-gray-500">Compression Ratio</dt>
                <dd class="font-semibold">
                    {{ $percentage ? number_format($percentage, 2) . '%' : '-' }}
                </dd>

                @if ($percentage)
                    <p class="text-xs text-gray-500 mt-1">
                        @if ($percentage < 100)
                            <span class="text-success-600 font-medium">
                                {{ 100 - number_format($percentage, 2) }}% smaller
                            </span>
                        @elseif ($percentage > 100)
                            <span class="text-warning-600 font-medium">
                                {{ number_format($percentage - 100, 2) }}% larger
                            </span>
                            (already-compressed file)
                        @else
                            No size reduction
                        @endif
                    </p>
                @endif
            </div>
        </dl>
    </x-filament::section>

    {{-- ================= PERFORMANCE ================= --}}
    <x-filament::section>
        <x-slot name="heading">Performance Benchmark</x-slot>

        <dl class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">
            <div>
                <dt class="text-gray-500">Compression Duration</dt>
                <dd class="font-medium">{{ $msToSecDetail($backup->duration_compress_ms) }}</dd>
            </div>

            <div>
                <dt class="text-gray-500">Encryption Duration (AES-256-GCM)</dt>
                <dd class="font-medium">{{ $msToSecDetail($backup->duration_encrypt_ms) }}</dd>
            </div>

            <div>
                <dt class="text-gray-500">Total Compress + Encrypt</dt>
                <dd class="font-semibold">{{ $msToSecDetail($backup->duration_total_ms) }}</dd>
            </div>

            <div>
                <dt class="text-gray-500">Decompression Duration</dt>
                <dd class="font-medium">{{ $msToSecDetail($backup->duration_decompress_ms) }}</dd>
            </div>

            <div>
                <dt class="text-gray-500">Decryption Duration</dt>
                <dd class="font-medium">{{ $msToSecDetail($backup->duration_decrypt_ms) }}</dd>
            </div>

            <div>
                <dt class="text-gray-500">Total Decrypt + Decompress</dt>
                <dd class="font-semibold">{{ $msToSecDetail($backup->duration_total_decompress) }}</dd>
            </div>
        </dl>
    </x-filament::section>

    {{-- ================= INTEGRITY ================= --}}
    <x-filament::section>
        <x-slot name="heading">Integrity Verification</x-slot>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 text-sm">
            <div>
                <dt class="text-gray-500">Original SHA-256</dt>
                <dd class="font-mono text-xs break-all">{{ $backup->original_sha256 }}</dd>
            </div>

            <div>
                <dt class="text-gray-500">After Decrypt SHA-256</dt>
                <dd class="font-mono text-xs break-all">{{ $backup->after_sha256 ?? '-' }}</dd>
            </div>

            <div>
                <dt class="text-gray-500">Integrity Status</dt>
                <dd>
                    @if ($backup->after_sha256)
                        <x-filament::badge color="{{ $backup->integrity_passed ? 'success' : 'danger' }}">
                            {{ $backup->integrity_passed ? 'PASSED' : 'FAILED' }}
                        </x-filament::badge>
                    @else
                        <x-filament::badge color="gray">Not Checked</x-filament::badge>
                    @endif
                </dd>
            </div>
        </div>
    </x-filament::section>

    {{-- ACTION --}}
    <div class="mt-6 flex justify-end">
        <x-filament::button
            wire:click="measureDecryptTime"
            icon="heroicon-o-clock"
            color="primary">
            Measure Integrity & Decrypt Time
        </x-filament::button>
    </div>

</div>
</x-filament-panels::page>
