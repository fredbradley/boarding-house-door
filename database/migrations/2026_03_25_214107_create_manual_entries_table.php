<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('manual_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('screen_id')->constrained()->cascadeOnDelete();
            $table->string('heading');
            $table->string('subheading')->nullable();
            $table->datetime('starts_at');
            $table->datetime('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('manual_entries');
    }
};
