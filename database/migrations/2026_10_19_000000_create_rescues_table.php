<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rescues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('created_by')->constrained('users');
            $table->date('happened_on');
            $table->string('location');
            $table->text('description')->nullable();
            $table->string('image_path');
            $table->timestamps();
        });

        Schema::create('rescue_user', function (Blueprint $table) {
            $table->foreignId('rescue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role', 20);
            $table->primary(['rescue_id', 'user_id', 'role']);
            $table->index(['role', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rescue_user');
        Schema::dropIfExists('rescues');
    }
};
