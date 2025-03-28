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
        Schema::create('audit_trail_rm', function (Blueprint $table) {
            $table->id();
            $table->string('object_id');
            $table->foreignId('action_id')->constrained('audit_trail_rm_actions');
            $table->foreignId('user_id')->constrained('users');
            $table->string('user_email');
            $table->text('data');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_trail_rm');
    }
};
