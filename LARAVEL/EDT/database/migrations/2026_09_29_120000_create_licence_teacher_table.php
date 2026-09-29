<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('licence_teacher', function (Blueprint $table) {
            $table->id();
            $table->foreignId('licence_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['licence_id', 'teacher_id']);
        });

        // Les enseignants existants gardent l'accès aux licences où ils ont déjà des cours.
        $now = now();
        DB::table('lessons')
            ->join('groups', 'groups.id', '=', 'lessons.group_id')
            ->select('groups.licence_id', 'lessons.teacher_id')
            ->distinct()
            ->get()
            ->chunk(500)
            ->each(fn ($rows) => DB::table('licence_teacher')->insert($rows->map(fn ($row) => [
                'licence_id' => $row->licence_id,
                'teacher_id' => $row->teacher_id,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all()));
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('licence_teacher');
    }
};
