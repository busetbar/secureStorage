<x-filament-panels::page>

    {{-- HEADER --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold tracking-tight">Upload Backup</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm">
            Upload file backup ke Go Worker dengan kompresi & enkripsi otomatis.
        </p>
    </div>

    {{-- CARD --}}
    <div class="bg-white dark:bg-gray-900 shadow-sm rounded-xl border border-gray-200 dark:border-gray-800 p-6 max-w-xl">

        <h3 class="font-semibold text-lg mb-4">Upload File</h3>

        <div class="space-y-4">

            {{-- BACKUP NAME --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Backup Name
                </label>
                <input id="backup-name"
                       type="text"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800
                              focus:ring-primary-500 focus:border-primary-500 shadow-sm">
            </div>

            {{-- FILE INPUT --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                    Select File
                </label>
                <input id="file-input"
                       type="file"
                       class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800
                              file:bg-gray-100 dark:file:bg-gray-700 file:border-none file:px-4 file:py-2 file:rounded-md
                              file:text-gray-700 dark:file:text-gray-300 shadow-sm">
            </div>

            {{-- BUTTON --}}
            <x-filament::button id="upload-btn"
                                color="primary"
                                class="w-full py-3 text-base font-medium rounded-lg">
                🚀 Start Upload
            </x-filament::button>
        </div>
    </div>

    {{-- PROGRESS CARD --}}
    <div id="progress-wrapper"
         class="hidden bg-white dark:bg-gray-900 border border-gray-200 dark:border-gray-800 shadow-sm rounded-xl p-6 mt-6 max-w-xl">

        <h3 class="font-semibold text-lg mb-4">Upload Progress</h3>

        <div class="space-y-3">

            <div id="progress-status"
                 class="font-medium text-primary-600 dark:text-primary-400">
            </div>

            {{-- PROGRESS BAR --}}
            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
                <div id="progress-bar"
                     class="h-3 bg-primary-500 transition-all duration-300"
                     style="width: 0%">
                </div>
            </div>

            <div id="progress-detail"
                 class="text-xs text-gray-500 dark:text-gray-400">
            </div>

        </div>
    </div>

</x-filament-panels::page>


<script>
(() => {

    const goUploadUrl = "http://192.168.200.211:9090/upload";
    const metadataUrl = "{{ route('backup.metadata.store') }}";
    const csrf = "{{ csrf_token() }}";

    const btnUpload = document.getElementById("upload-btn");
    const progressBox = document.getElementById("progress-wrapper");
    const bar = document.getElementById("progress-bar");
    const status = document.getElementById("progress-status");
    const detail = document.getElementById("progress-detail");

    btnUpload.addEventListener("click", () => {

        const file = document.getElementById("file-input").files[0];
        const name = document.getElementById("backup-name").value || (file ? file.name : '');

        if (!file) return alert("Please select a file first!");

        // Disable button to prevent double upload
        btnUpload.disabled = true;
        btnUpload.textContent = "Uploading...";

        // Show progress container
        progressBox.classList.remove("hidden");

        // STEP 1 — Send metadata to Laravel
        fetch(metadataUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrf,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                name: name,
                original_filename: file.name,
                original_size: file.size,
            })
        })
        .then(r => r.text())
        .then(t => JSON.parse(t))
        .then(meta => {

            const uploadUrl =
                `${goUploadUrl}?filename=${encodeURIComponent(file.name)}&backup_id=${meta.backup_id}`;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", uploadUrl);

            // Progress bar update
            xhr.upload.onprogress = (e) => {
                const pct = (e.loaded / e.total) * 100;

                bar.style.width = pct + "%";
                status.textContent = `Uploading ${pct.toFixed(2)}%`;
                detail.textContent = 
                    `${(e.loaded/1024/1024).toFixed(2)} MB / ${(e.total/1024/1024).toFixed(2)} MB`;
            };

            // Poll Go Worker until completion
            function waitStatus(id) {
                const timer = setInterval(() => {
                    fetch(`/api/backup/status/${id}`)
                        .then(r => r.json())
                        .then(resp => {

                            if (resp.status === "completed") {
                                clearInterval(timer);

                                status.textContent = "Upload Complete ✔";
                                bar.style.width = "100%";

                                setTimeout(() => {
                                    window.location.href = "/admin/backups";
                                }, 700);
                            }
                        })
                        .catch(console.error);
                }, 1500);
            }

            xhr.onload = () => {
                status.textContent = "Processing file...";
                waitStatus(meta.backup_id);
            };

            const fd = new FormData();
            fd.append("file", file);
            xhr.send(fd);
        });

    });

})();
</script>
