<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Médailles. athlete_id est vide pour une épreuve par équipes (la médaille revient au pays) ;
     * medal : gold, silver ou bronze, deux bronzes possibles (voir sports.two_bronzes).
     */
    public function up(): void
    {
        Schema::create('results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('country_id')->constrained()->cascadeOnDelete();
            $table->foreignId('athlete_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('medal');
            $table->unique(['event_id', 'athlete_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('results');
    }
};
