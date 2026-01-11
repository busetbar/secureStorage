<x-filament-panels::page>

    {{-- HEADER --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Upload Form</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
            File akan di-hash untuk pengecekan integrity, lalu di-upload,
            dikompresi, dan dienkripsi oleh Go Worker.
        </p>
    </div>

    {{-- UPLOAD CARD --}}
    <div class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-800 
                rounded-xl p-6 max-w-xl space-y-6">

        {{-- INPUT --}}
        <div class="space-y-4">
            <div>
                <label class="text-sm font-medium">Backup Name</label>
                <input id="backup-name"
                       type="text"
                       class="w-full rounded-lg border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-800
                              focus:ring-primary-500 focus:border-primary-500 shadow-sm">
            </div>

            <div>
                <label class="text-sm font-medium">Choose File</label>
                <input id="file-input"
                       type="file"
                       class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-700 
                              bg-white dark:bg-gray-800 shadow-sm">
            </div>

            <x-filament::button id="upload-btn"
                                color="primary"
                                class="w-full py-3 text-base font-medium rounded-lg">
                🚀 Start Upload
            </x-filament::button>
        </div>

        {{-- PROGRESS --}}
        <div id="progress-wrapper"
             class="hidden space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">

            <h3 class="font-semibold text-lg" id="progress-title">Preparing...</h3>

            <div id="progress-status"
                 class="font-medium text-primary-600 dark:text-primary-400"></div>

            <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div id="progress-bar"
                     class="h-3 bg-primary-500 transition-all duration-300"
                     style="width: 0%"></div>
            </div>

            <div id="progress-detail"
                 class="text-xs text-gray-500 dark:text-gray-400"></div>
        </div>

        {{-- BACKGROUND SPINNER (SATU SAJA) --}}
        <div id="background-processing"
             class="hidden flex items-center gap-2 text-sm text-yellow-600 dark:text-yellow-400 pt-2">
            <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24">
                <circle cx="12" cy="12" r="10"
                        stroke="currentColor"
                        stroke-width="4"
                        fill="none"
                        stroke-linecap="round"
                        stroke-dasharray="60"
                        stroke-dashoffset="20"/>
            </svg>
            <span>Processing in background (compression & encryption)…</span>
        </div>

    </div>

</x-filament-panels::page>


<script src="https://cdnjs.cloudflare.com/ajax/libs/js-sha256/0.9.0/sha256.min.js"></script>
<script>
function computeSHA256(file, onProgress) {
    const CHUNK = 2 * 1024 * 1024;
    let offset = 0;
    const sha = sha256.create();
    const reader = new FileReader();

    return new Promise((resolve, reject) => {
        reader.onerror = () => reject(reader.error);

        reader.onload = e => {
            const chunk = new Uint8Array(e.target.result);
            sha.update(chunk);
            offset += chunk.length;

            if (onProgress) onProgress(offset, file.size);

            if (offset < file.size) {
                reader.readAsArrayBuffer(file.slice(offset, offset + CHUNK));
            } else {
                resolve(sha.hex());
            }
        };

        reader.readAsArrayBuffer(file.slice(0, CHUNK));
    });
}
</script>
<script>
(() => {

    const uploadUrlGo  = "http://192.168.200.211:9090/upload";
    const metadataUrl  = "{{ route('backup.metadata.store') }}";
    const csrf         = "{{ csrf_token() }}";

    const btn          = document.getElementById("upload-btn");
    const progressBox  = document.getElementById("progress-wrapper");
    const progressBar  = document.getElementById("progress-bar");
    const progressStat = document.getElementById("progress-status");
    const progressDet  = document.getElementById("progress-detail");
    const progressTitle= document.getElementById("progress-title");
    const bgSpinner    = document.getElementById("background-processing");

    btn.addEventListener("click", async () => {

        const file = document.getElementById("file-input").files[0];
        const backupName = document.getElementById("backup-name").value || file?.name;

        if (!file) return alert("Please choose a file first.");

        // 🔄 Spinner ON dari awal
        bgSpinner.classList.remove("hidden");

        btn.disabled = true;
        btn.textContent = "Processing...";
        progressBox.classList.remove("hidden");

        /* STEP 1: HASHING */
        progressTitle.textContent = "Hashing file…";
        progressStat.textContent  = "Checking integrity…";

        const sha = await computeSHA256(file, (loaded, total) => {
            const pct = loaded / total * 100;
            progressBar.style.width = pct + "%";
            progressStat.textContent = `Hashing ${pct.toFixed(1)}%`;
        });

        /* STEP 2: METADATA */
        progressTitle.textContent = "Sending metadata…";

        const meta = await fetch(metadataUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrf,
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                name: backupName,
                original_filename: file.name,
                original_size: file.size,
                original_sha256: sha
            })
        }).then(r => r.json());

        /* STEP 3: UPLOAD */
        progressTitle.textContent = "Uploading file…";
        progressBar.style.width = "0%";

        const xhr = new XMLHttpRequest();
        xhr.open("POST", `${uploadUrlGo}?filename=${encodeURIComponent(file.name)}&backup_id=${meta.backup_id}`);

        xhr.upload.onprogress = e => {
            const pct = e.loaded / e.total * 100;
            progressBar.style.width = pct + "%";
            progressStat.textContent = `Uploading ${pct.toFixed(1)}%`;
            progressStat.textContent =
                `Uploading file… ${pct.toFixed(1)}%`;
        };

        xhr.onload = () => {
            progressTitle.textContent = "Upload completed";
            progressStat.textContent  = "Processing in background…";
            progressBar.classList.add("animate-pulse");
            pollStatus(meta.backup_id);
        };

        const fd = new FormData();
        fd.append("file", file);
        xhr.send(fd);
    });

    function pollStatus(id) {
        const timer = setInterval(() => {
            fetch(`/api/backup/status/${id}`)
                .then(r => r.json())
                .then(res => {
                    if (res.status === "completed") {
                        clearInterval(timer);

                        bgSpinner.classList.add("hidden");
                        progressBar.classList.remove("animate-pulse");
                        progressBar.style.width = "100%";
                        progressTitle.textContent = "Process Completed ✔";

                        setTimeout(() => {
                            window.location.href = "/admin/backups";
                        }, 800);
                    }
                });
        }, 1200);
    }

})();
</script>
