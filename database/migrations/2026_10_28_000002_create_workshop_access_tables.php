<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_garage_manager')->default(false);
        });
        Schema::create('garage_invitations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('garage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('invited_by')->constrained('users');
            $table->string('email');
            $table->string('token_hash', 64)->unique();
            $table->timestamp('expires_at');
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();
        });
        Schema::create('car_garage_access', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();
            $table->foreignId('garage_id')->constrained()->cascadeOnDelete();
            $table->foreignId('owner_id')->constrained('users');
            $table->timestamp('granted_at');
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();
            $table->unique(['car_id', 'garage_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_garage_access');
        Schema::dropIfExists('garage_invitations');
        Schema::table('users', fn (Blueprint $table) => $table->dropColumn('is_garage_manager'));
    }
};
