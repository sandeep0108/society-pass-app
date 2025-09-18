<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('scan_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('visitor_id')->nullable()->constrained()->onDelete('set null');
            $table->string('scanned_data'); // Raw data from the QR code
            $table->boolean('is_valid_pass')->default(false);
            $table->text('notes')->nullable(); // e.g., "Entry Granted", "Pass Expired"
            $table->foreignId('scanned_by_user_id')->nullable()->constrained('users')->onDelete('set null'); // Security guard
            $table->ipAddress('scanner_ip')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scan_logs');
    }
};
