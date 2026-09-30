<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pays. iso : code ISO 3166-1 alpha-2, qui donne le drapeau (public/img/flags).
     */
    public function up(): void
    {
        Schema::create('countries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code', 3)->unique();
            $table->string('iso', 2);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('countries');
    }
};
