<?php

namespace App\Actions;

use App\Models\Lesson;
use App\Models\Teacher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferLessons
{
    /**
     * Confie les cours d'un enseignant (tous, ou ceux d'une matière) à un autre enseignant.
     * Le remplaçant doit enseigner les matières concernées et être libre sur chaque créneau.
     *
     * @return int Nombre de cours transférés
     *
     * @throws ValidationException
     */
    public function handle(Teacher $from, Teacher $to, ?int $subjectId = null): int
    {
        $lessons = $from->lessons()
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->with('subject')
            ->get();

        if ($lessons->isEmpty()) {
            $this->fail("{$from->full_name} n'a aucun cours à transférer.");
        }

        $taught = $to->subjects()->pluck('subjects.id');
        $missing = $lessons->pluck('subject')->unique('id')->reject(fn ($subject) => $taught->contains($subject->id));

        if ($missing->isNotEmpty()) {
            $this->fail("{$to->full_name} n'enseigne pas : {$missing->pluck('name')->join(', ')}. Ajoute d'abord cette matière à son profil.");
        }

        $busyCells = $to->lessons()->get()->map->cellKey();
        $conflicts = $lessons->filter(fn (Lesson $lesson) => $busyCells->contains($lesson->cellKey()));

        if ($conflicts->isNotEmpty()) {
            $slots = $conflicts->map(fn (Lesson $lesson) => $lesson->day_of_week->label().' '.$lesson->start_time->format('H:i'))->join(', ');
            $this->fail("{$to->full_name} a déjà un cours sur : {$slots}.");
        }

        try {
            DB::transaction(fn () => Lesson::whereIn('id', $lessons->modelKeys())->update(['teacher_id' => $to->id]));
        } catch (UniqueConstraintViolationException) {
            $this->fail("{$to->full_name} vient d'être réservé sur l'un de ces créneaux, réessaie.");
        }

        return $lessons->count();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['replacement_id' => $message]);
    }
}
