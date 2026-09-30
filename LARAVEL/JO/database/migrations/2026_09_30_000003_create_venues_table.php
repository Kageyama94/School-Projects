<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sites de compétition.
     */
    public function up(): void
    {
        Schema::create('venues', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('city');
            $table->unsignedInteger('capacity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('venues');
    }
};
