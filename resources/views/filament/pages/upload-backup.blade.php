<x-filament-panels::page>

    <h2 class="text-xl font-bold mb-4">Upload Backup (Go Worker)</h2>

    {{-- INPUT --}}
    <div class="space-y-4 max-w-xl">

        <div>
            <label class="text-sm font-medium">Backup Name</label>
            <input id="backup-name" type="text" class="fi-input w-full mt-1">
        </div>

        <div>
            <label class="text-sm font-medium">File</label>
            <input id="file-input" type="file" class="fi-input w-full mt-1">
        </div>

        <x-filament::button id="upload-btn" color="primary">
            Upload
        </x-filament::button>

        {{-- Progress --}}
        <div id="progress-wrapper" class="hidden space-y-2 mt-4">
            <div id="progress-status"></div>
            <div class="h-2 bg-gray-700 rounded overflow-hidden">
                <div id="progress-bar" class="h-2 bg-primary-500 w-0"></div>
            </div>
            <div id="progress-detail" class="text-xs text-gray-400"></div>
        </div>

    </div>

</x-filament-panels::page>

<script>
(() => {
    const goUploadUrl = "http://192.168.200.211:9090/upload";
    const metadataUrl = "{{ route('backup.metadata.store') }}";
    const csrf = "{{ csrf_token() }}";

    document.getElementById("upload-btn").addEventListener("click", () => {

        const file = document.getElementById("file-input").files[0];
        if (!file) return alert("Select a file first");

        // 1. Create metadata FIRST
        fetch(metadataUrl, {
            method: "POST",
            headers: {
                "X-CSRF-TOKEN": csrf,
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                name: document.getElementById("backup-name").value || file.name,
                original_filename: file.name,
                original_size: file.size,
            })
        })
        .then(async r => {
            const text = await r.text();
            console.log("RAW RESPONSE:", text);
            return JSON.parse(text);
        })
        .then(meta => {

            const uploadUrl =
                `${goUploadUrl}?filename=${encodeURIComponent(file.name)}&backup_id=${meta.backup_id}`;

            const xhr = new XMLHttpRequest();
            xhr.open("POST", uploadUrl, true);

            const progress = document.getElementById("progress-wrapper");
            const bar      = document.getElementById("progress-bar");
            const status   = document.getElementById("progress-status");
            const detail   = document.getElementById("progress-detail");

            progress.classList.remove("hidden");

            xhr.upload.onprogress = (e) => {
                const pct = (e.loaded / e.total) * 100;
                bar.style.width = pct + "%";
                status.textContent = `Uploading ${pct.toFixed(2)}%`;
                detail.textContent = `${(e.loaded/1024/1024).toFixed(2)}MB / ${(e.total/1024/1024).toFixed(2)}MB`;
            };

            function waitStatus(backupId) {
    const interval = setInterval(() => {

        fetch(`/api/backup/status/${backupId}`)
            .then(r => r.json())
            .then(res => {

                console.log("Polling status:", res);

                if (res.status === "completed") {
                    clearInterval(interval);

                    document.getElementById("progress-status").textContent =
                        "Upload Complete ✔";

                    // 🔥 Redirect ke halaman table backups
                    setTimeout(() => {
                        window.location.href = "/admin/backups";
                    }, 800);
                }
            })

            .catch(err => console.error("Polling error:", err));

    }, 1500);
}

            xhr.onload = () => {
                status.textContent = "Waiting for Go Worker to finalize...";
                waitStatus(meta.backup_id);
            };

            const fd = new FormData();
            fd.append("file", file);
            xhr.send(fd);
        });
    });
})();
</script>
