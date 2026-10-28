<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workshop_customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->string('name');
            $table->string('phone', 30);
            $table->string('plate_number', 100);
            $table->string('make');
            $table->string('model');
            $table->unsignedSmallInteger('year');
            $table->text('notes')->nullable();
            $table->string('claim_token_hash', 64)->nullable()->unique();
            $table->timestamp('claim_expires_at')->nullable();
            $table->foreignId('claimed_car_id')->nullable()->constrained('cars')->nullOnDelete();
            $table->foreignId('claimed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('claimed_at')->nullable();
            $table->timestamps();
            $table->index(['garage_id', 'phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workshop_customers');
    }
};
