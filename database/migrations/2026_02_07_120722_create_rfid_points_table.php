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
        Schema::create('rfid_points', function (Blueprint $table) {
            $table->id();
            $table->foreignId('truck_id')->constrained('trucks')->onDelete('cascade');
            $table->string('rfid_code')->unique();
            $table->string('location')->comment('Lokasi titik tap pada truck');
            $table->integer('point_number')->comment('Urutan titik (1-5)');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            // Unique constraint: satu truck tidak boleh punya lokasi yang sama
            $table->unique(['truck_id', 'location']);
            // Unique constraint: satu truck tidak boleh punya point_number yang sama
            $table->unique(['truck_id', 'point_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rfid_points');
    }
};
