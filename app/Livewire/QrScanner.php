<?php
// app/Livewire/QrScanner.php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Visitor;
use App\Models\ScanLog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class QrScanner extends Component
{
    public $scannedData = '';
    public $scanResult = null; // To hold Visitor data if found
    public $scanStatus = null; // 'success', 'warning', 'error'
    public $message = '';

    protected $listeners = ['qrCodeScanned' => 'handleQrCodeScanned'];

    public function mount()
    {
        // Optional: Pre-fill scannedData for testing purposes if needed
        // $this->scannedData = 'TEST_QR_DATA_123';
        // $this->processScan();
    }

    public function handleQrCodeScanned($data)
    {
        $this->scannedData = $data;
        $this->processScan();
    }

    public function processScan()
    {
        if (empty($this->scannedData)) {
            $this->message = 'No QR code data to process.';
            $this->scanStatus = 'error';
            return;
        }

        $visitor = Visitor::where('qr_code_data', $this->scannedData)->first();
        $is_valid_pass = false;
        $notes = 'Pass Scanned.';

        if ($visitor) {
            $this->scanResult = $visitor;
            $now = Carbon::now();

            if (!$visitor->is_active) {
                $this->message = "Pass for {$visitor->name} is INACTIVE.";
                $this->scanStatus = 'error';
                $notes .= ' Status: Inactive.';
            } elseif ($visitor->valid_from && $now->isBefore($visitor->valid_from)) {
                $this->message = "Pass for {$visitor->name} is NOT YET VALID (Valid from: {$visitor->valid_from->format('M d, Y H:i')}).";
                $this->scanStatus = 'warning';
                $notes .= ' Status: Not yet valid.';
            } elseif ($visitor->valid_until && $now->isAfter($visitor->valid_until)) {
                $this->message = "Pass for {$visitor->name} has EXPIRED (Expired on: {$visitor->valid_until->format('M d, Y H:i')}).";
                $this->scanStatus = 'error';
                $notes .= ' Status: Expired.';
            } else {
                $this->message = "VALID Pass for {$visitor->name}. Purpose: {$visitor->purpose}.";
                $this->scanStatus = 'success';
                $is_valid_pass = true;
                $notes .= ' Status: Valid. Entry granted.';
            }
        } else {
            $this->scanResult = null;
            $this->message = 'QR Code not recognized or invalid pass data.';
            $this->scanStatus = 'error';
            $notes .= ' Status: Unrecognized QR.';
        }

        // Log the scan attempt
        ScanLog::create([
            'visitor_id' => $visitor ? $visitor->id : null,
            'scanned_data' => $this->scannedData,
            'is_valid_pass' => $is_valid_pass,
            'notes' => $notes,
            'scanned_by_user_id' => Auth::id(), // Log the user who scanned
            'scanner_ip' => request()->ip(),
        ]);

        session()->flash('message', $this->message);
        session()->flash('message_type', $this->scanStatus);

        // This would typically trigger a UI update to show results dynamically
        // and hide the scanner temporarily or allow for a new scan.
    }

    public function resetScanner()
    {
        $this->scannedData = '';
        $this->scanResult = null;
        $this->scanStatus = null;
        $this->message = '';
        session()->forget(['message', 'message_type']);
        $this->dispatch('resetQrScanner'); // Dispatch event to reset frontend scanner
    }
    
    public function render()
    {
        return view('livewire.qr-scanner');
    }
}
