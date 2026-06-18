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
        Schema::create('driver_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('order_id'); // Menampung ID order berupa string biasa (tanpa foreign key ke DB luar)
            $table->foreignId('driver_id')->constrained('drivers')->onDelete('cascade'); // Foreign key lokal ke tabel drivers kamu
            $table->enum('status', ['assigned', 'ongoing', 'completed'])->default('assigned');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('driver_assignments');
    }
};
