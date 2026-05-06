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
        Schema::create('reviews', function (Blueprint $table) {
        $table->id();
        $table->foreignId('owner_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('mechanic_id')->constrained('users')->cascadeOnDelete();
        $table->foreignId('service_record_id')->constrained()->cascadeOnDelete();
        $table->unsignedTinyInteger('rating');
        $table->text('comment')->nullable();
        $table->timestamps();

        $table->unique(['owner_id', 'service_record_id']);
    });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
