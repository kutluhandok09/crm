@props([
    'targetInput',
    'title' => 'Barkod Tara',
    'scannerId' => null,
])

@php
    $resolvedScannerId = $scannerId ?: 'barcode-scanner-' . \Illuminate\Support\Str::uuid();
@endphp

<div class="rounded-md border border-gray-200 p-4" x-data="barcodeScannerComponent('{{ $resolvedScannerId }}', '{{ $targetInput }}')">
    <div class="flex flex-wrap items-center gap-2">
        <button type="button"
            @click="toggleScanner"
            class="inline-flex items-center rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
            <span x-text="started ? 'Kamerayi Kapat' : '{{ $title }}'"></span>
        </button>
        <p class="text-xs text-gray-500">Telefonlarda arka kamera otomatik secilmeye calisilir.</p>
    </div>
    <div :id="scannerId" class="mt-3"></div>
</div>

@once
    <script src="https://unpkg.com/html5-qrcode" defer></script>
    <script>
        function barcodeScannerComponent(scannerId, targetInputId) {
            return {
                scannerId,
                targetInputId,
                started: false,
                scanner: null,
                async toggleScanner() {
                    if (this.started) {
                        await this.stopScanner();
                    } else {
                        await this.startScanner();
                    }
                },
                async startScanner() {
                    if (typeof Html5Qrcode === 'undefined') {
                        return;
                    }

                    if (!this.scanner) {
                        this.scanner = new Html5Qrcode(this.scannerId);
                    }

                    const cameras = await Html5Qrcode.getCameras();
                    if (!cameras || cameras.length === 0) {
                        return;
                    }

                    const preferred = cameras.find((camera) =>
                        /back|rear|environment/i.test(camera.label || '')
                    );

                    const selectedCameraId = (preferred || cameras[0]).id;

                    await this.scanner.start(
                        selectedCameraId,
                        { fps: 10, qrbox: { width: 250, height: 120 } },
                        (decodedText) => {
                            const target = document.getElementById(this.targetInputId);
                            if (!target) {
                                return;
                            }

                            target.value = decodedText;
                            target.dispatchEvent(new Event('input', { bubbles: true }));
                            this.stopScanner();
                        },
                        () => {}
                    );

                    this.started = true;
                },
                async stopScanner() {
                    if (!this.scanner || !this.started) {
                        return;
                    }

                    await this.scanner.stop();
                    await this.scanner.clear();
                    this.started = false;
                }
            };
        }
    </script>
@endonce
