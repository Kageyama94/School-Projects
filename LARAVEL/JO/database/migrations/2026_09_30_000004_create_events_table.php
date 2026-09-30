<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Épreuves. gender : F, H ou M (mixte) ; team : médaille attribuée à un pays et non à un athlète ;
     * cancelled_at : épreuve annulée, billets remboursés.
     */
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sport_id')->constrained()->cascadeOnDelete();
            $table->foreignId('venue_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('gender', 1);
            $table->boolean('team')->default(false);
            $table->dateTime('starts_at');
            $table->unsignedInteger('price');
            $table->unsignedInteger('capacity');
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
