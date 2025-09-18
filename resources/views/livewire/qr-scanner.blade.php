<!-- resources/views/livewire/qr-scanner.blade.php -->
<div class="min-h-screen bg-gray-100 flex flex-col items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-lg shadow-lg p-6">
        <h1 class="text-2xl font-bold text-center text-gray-800 mb-6">Society Pass Scanner</h1>

        {{-- Video stream for QR scanning --}}
        <div class="relative w-full h-64 bg-gray-200 rounded-md overflow-hidden mb-4 border-2 border-dashed border-gray-400 flex items-center justify-center">
            <video id="qr-video" class="w-full h-full object-cover"></video>
            <div id="loading-message" class="absolute text-gray-600 @if($scannedData) hidden @endif">Loading scanner...</div>
            {{-- Optional: Overlay for scanning area --}}
            <div class="absolute inset-0 flex items-center justify-center pointer-events-none">
                <div class="w-48 h-48 border-4 border-blue-500 rounded-lg opacity-75"></div>
            </div>
        </div>

        <p id="scan-instructions" class="text-center text-gray-600 mb-4 @if($scannedData) hidden @endif">
            Align a QR code within the blue frame.
        </p>

        {{-- Scan Result Display --}}
        @if ($scannedData && $scanStatus)
            <div id="scan-result" class="p-3 rounded-md text-center mb-4
                @if($scanStatus == 'success') bg-green-100 text-green-800
                @elseif($scanStatus == 'warning') bg-yellow-100 text-yellow-800
                @elseif($scanStatus == 'error') bg-red-100 text-red-800
                @endif">
                <p class="font-semibold">{{ $message }}</p>
                @if ($scanResult)
                    <p class="mt-2 text-sm">Visitor: <span class="font-medium">{{ $scanResult->name }}</span></p>
                    <p class="text-sm">QR Data: <span class="break-all">{{ $scanResult->qr_code_data }}</span></p>
                @else
                    <p class="mt-2 text-sm">Scanned Data: <span class="break-all">{{ $scannedData }}</span></p>
                @endif
            </div>
        @endif


        {{-- Action Buttons --}}
        <div class="mt-6 flex justify-center space-x-4">
            {{-- Optionally add a "Grant Entry" button if the pass is valid and requires manual approval --}}
            @if ($scanStatus == 'success' && $scanResult)
                <button wire:click="grantEntry('{{ $scanResult->id }}')" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-6 rounded-lg shadow-md transition duration-300">
                    Grant Entry
                </button>
            @endif
            <button wire:click="resetScanner" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-bold py-2 px-6 rounded-lg shadow-md transition duration-300">
                {{ $scannedData ? 'Scan New' : 'Reset' }}
            </button>
        </div>

        {{-- Error/Status Messages (for Livewire flashes) --}}
        @if (session()->has('message'))
            <div class="mt-4 p-3 rounded-md {{ session('message_type') == 'success' ? 'bg-green-100 text-green-800' : (session('message_type') == 'warning' ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') }}">
                {{ session('message') }}
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    const qrVideo = document.getElementById('qr-video');
    const loadingMessage = document.getElementById('loading-message');
    const scanInstructions = document.getElementById('scan-instructions');
    let html5QrCode = null;

    function startScanner() {
        if (html5QrCode) {
            html5QrCode.stop().catch(err => console.error("Failed to stop existing scanner", err));
            html5QrCode = null;
        }

        html5QrCode = new Html5Qrcode("qr-video");
        html5QrCode.start(
            { facingMode: "environment" }, // Prefer rear camera
            {
                fps: 10,    // frames per second
                qrbox: { width: 250, height: 250 } // Size of the scanning box
            },
            (decodedText, decodedResult) => {
                // When a QR code is successfully scanned
                console.log(`QR Code detected: ${decodedText}`);
                @this.dispatch('qrCodeScanned', { data: decodedText });
                html5QrCode.stop().catch(err => console.error("Failed to stop scanner after scan", err));
                loadingMessage.classList.add('hidden');
                scanInstructions.classList.add('hidden');
            },
            (errorMessage) => {
                // On error or no QR code found
                // console.log(`No QR code detected: ${errorMessage}`);
                loadingMessage.classList.remove('hidden');
                scanInstructions.classList.remove('hidden');
            }
        ).catch((err) => {
            console.error("Unable to start scanning, please grant camera access.", err);
            loadingMessage.innerText = "Error: Camera access denied or not available.";
            loadingMessage.classList.remove('hidden');
            scanInstructions.classList.add('hidden');
        });
    }

    // Start scanner when Livewire component loads
    document.addEventListener('livewire:init', () => {
        startScanner();
    });

    // Listen for reset event from Livewire
    @this.on('resetQrScanner', () => {
        console.log('Resetting scanner...');
        startScanner();
        // Clear previous messages / results
        document.getElementById('scan-result').classList.add('hidden');
    });

    // Handle component unmount to stop camera
    window.addEventListener('beforeunload', () => {
        if (html5QrCode) {
            html5QrCode.stop().catch(err => console.error("Failed to stop scanner on unload", err));
        }
    });

    // Livewire will re-render, so need to ensure scanner restarts if needed
    document.addEventListener('livewire:navigated', () => {
        if (!@this.get('scannedData')) { // Only restart if not showing results
             startScanner();
        } else {
             if (html5QrCode) {
                html5QrCode.stop().catch(err => console.error("Failed to stop scanner after nav (data exists)", err));
             }
        }
    });

</script>
@endpush