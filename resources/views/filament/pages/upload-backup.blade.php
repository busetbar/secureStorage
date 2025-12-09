<x-filament-panels::page>

    {{-- HEADER --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Upload Backup</h1>
        <p class="text-gray-500 dark:text-gray-400 text-sm mt-1">
            File akan di-hash (SHA-256), lalu di-upload & dienkripsi oleh Go Worker.
        </p>
    </div>

    {{-- UPLOAD CARD --}}
    <div class="bg-white dark:bg-gray-900 shadow-sm border border-gray-200 dark:border-gray-800 
                rounded-xl p-6 max-w-xl space-y-6">

        {{-- Input --}}
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
                              bg-white dark:bg-gray-800 shadow-sm file:bg-gray-100 dark:file:bg-gray-700
                              file:border-none file:px-4 file:py-2 file:rounded-md">
            </div>

            <x-filament::button id="upload-btn"
                                color="primary"
                                class="w-full py-3 text-base font-medium rounded-lg">
                🚀 Start Upload
            </x-filament::button>
        </div>

        {{-- PROGRESS WRAPPER --}}
        <div id="progress-wrapper" class="hidden space-y-4 pt-4 border-t border-gray-200 dark:border-gray-700">

            <h3 class="font-semibold text-lg" id="progress-title">Preparing...</h3>

            {{-- Status Text --}}
            <div id="progress-status" class="font-medium text-primary-600 dark:text-primary-400"></div>

            {{-- Progress Bar --}}
            <div class="w-full h-3 bg-gray-200 dark:bg-gray-700 rounded-full overflow-hidden">
                <div id="progress-bar"
                     class="h-3 bg-primary-500 transition-all duration-300"
                     style="width: 0%">
                </div>
            </div>

            {{-- Detail --}}
            <div id="progress-detail"
                 class="text-xs text-gray-500 dark:text-gray-400"></div>

        </div>

    </div>

</x-filament-panels::page>


<script src="https://cdnjs.cloudflare.com/ajax/libs/js-sha256/0.9.0/sha256.min.js"></script>

<script>
async function computeSHA256(file, onProgress) {
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

            offset < file.size
                ? readNext()
                : resolve(sha.hex());
        };

        function readNext() {
            reader.readAsArrayBuffer(file.slice(offset, offset + CHUNK));
        }

        readNext();
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

    btn.addEventListener("click", async () => {

        const file = document.getElementById("file-input").files[0];
        const backupName = document.getElementById("backup-name").value || file.name;

        if (!file) return alert("Please choose a file first.");

        btn.disabled = true;
        btn.textContent = "Processing...";

        progressBox.classList.remove("hidden");

        /* --------------------------
           STEP 1: HASHING
        ---------------------------*/
        progressTitle.textContent = "Computing SHA-256";
        progressStat.textContent = "Hashing...";

        const sha = await computeSHA256(file, (loaded, total) => {
            const pct = loaded / total * 100;

            progressBar.style.width = pct + "%";
            progressStat.textContent = `Hashing ${pct.toFixed(1)}%`;
            progressDet.textContent  =
                `${(loaded/1024/1024).toFixed(2)} / ${(total/1024/1024).toFixed(2)} MB`;
        });

        progressStat.textContent = `SHA-256 Ready ✔`;
        progressDet.textContent = sha;
        progressBar.style.width = "100%";


        /* --------------------------
           STEP 2: SEND METADATA
        ---------------------------*/
        progressTitle.textContent = "Sending Metadata to Server...";

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


        /* --------------------------
           STEP 3: UPLOAD FILE
        ---------------------------*/
        progressTitle.textContent = "Uploading File...";
        progressStat.textContent  = "Uploading 0%";
        progressBar.style.width   = "0%";

        const xhr = new XMLHttpRequest();
        xhr.open("POST", `${uploadUrlGo}?filename=${encodeURIComponent(file.name)}&backup_id=${meta.backup_id}`);

        xhr.upload.onprogress = e => {
            const pct = e.loaded / e.total * 100;

            progressBar.style.width = pct + "%";
            progressStat.textContent = `Uploading ${pct.toFixed(1)}%`;
            progressDet.textContent =
                `${(e.loaded/1024/1024).toFixed(2)} MB / ${(e.total/1024/1024).toFixed(2)} MB`;
        };

        xhr.onload = () => {
            progressTitle.textContent = "Processing on Go Worker...";
            pollStatus(meta.backup_id);
        };

        const fd = new FormData();
        fd.append("file", file);
        xhr.send(fd);

    });

    /* --------------------------
       Poll Until Completed
    ---------------------------*/
    function pollStatus(id) {
        const timer = setInterval(() => {
            fetch(`/api/backup/status/${id}`)
                .then(r => r.json())
                .then(res => {
                    if (res.status === "completed") {
                        clearInterval(timer);

                        progressTitle.textContent = "Upload Completed ✔";
                        progressBar.style.width = "100%";

                        setTimeout(() => {
                            window.location.href = "/admin/backups";
                        }, 600);
                    }
                })
        }, 1200);
    }

})();
</script>
