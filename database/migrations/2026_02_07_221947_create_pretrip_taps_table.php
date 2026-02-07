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
        Schema::create('pretrip_taps', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pretrip_id')->constrained('pretrips')->onDelete('cascade');
            $table->foreignId('rfid_point_id')->constrained('rfid_points')->onDelete('cascade');
            $table->timestamp('tapped_at');
            $table->integer('tap_sequence')->comment('Urutan tap (1, 2, 3, dst)');
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: satu pretrip tidak boleh tap titik yang sama lebih dari 1x
            $table->unique(['pretrip_id', 'rfid_point_id']);

            // Index untuk performa
            $table->index(['pretrip_id', 'tap_sequence']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pretrip_taps');
    }
};
