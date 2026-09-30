<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sports. two_bronzes : sports de combat, deux médailles de bronze par épreuve.
     */
    public function up(): void
    {
        Schema::create('sports', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('icon', 16);
            $table->text('description')->nullable();
            $table->boolean('two_bronzes')->default(false);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sports');
    }
};
