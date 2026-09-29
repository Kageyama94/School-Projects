<?php

namespace App\Actions;

use App\Models\Lesson;
use App\Models\Teacher;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransferLessons
{
    /**
     * Confie les cours d'un enseignant (tous, ou ceux d'une matière) à un autre enseignant.
     * Le remplaçant doit enseigner les matières concernées, avoir accès aux licences des groupes et être libre sur chaque créneau.
     *
     * @return int Nombre de cours transférés
     *
     * @throws ValidationException
     */
    public function handle(Teacher $from, Teacher $to, ?int $subjectId = null): int
    {
        $lessons = $from->lessons()
            ->when($subjectId, fn ($query) => $query->where('subject_id', $subjectId))
            ->with(['subject', 'group.licence'])
            ->get();

        if ($lessons->isEmpty()) {
            $this->fail("{$from->full_name} n'a aucun cours à transférer.");
        }

        $taught = $to->subjects()->pluck('subjects.id');
        $missing = $lessons->pluck('subject')->unique('id')->reject(fn ($subject) => $taught->contains($subject->id));

        if ($missing->isNotEmpty()) {
            $this->fail("{$to->full_name} n'enseigne pas : {$missing->pluck('name')->join(', ')}. Ajoute d'abord cette matière à son profil.");
        }

        $licenceIds = $to->licences()->pluck('licences.id');
        $missingLicences = $lessons->pluck('group.licence')->unique('id')->reject(fn ($licence) => $licenceIds->contains($licence->id));

        if ($missingLicences->isNotEmpty()) {
            $this->fail("{$to->full_name} n'a pas accès à : {$missingLicences->pluck('name')->join(', ')}. Ajoute d'abord cette licence à son profil.");
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

    /**
     * Enseignants capables de reprendre au moins une matière de cet enseignant : ils l'enseignent et ont
     * accès à toutes les licences où elle est donnée. `takes` indique, pour le formulaire, les matières
     * qu'ils peuvent reprendre (et `all` s'ils peuvent tout reprendre). Mêmes règles que handle() ; les créneaux libres ne sont vérifiés qu'au transfert.
     *
     * @return Collection<int, Teacher>
     */
    public function candidates(Teacher $teacher): Collection
    {
        $licencesBySubject = $teacher->lessons()
            ->join('groups', 'groups.id', '=', 'lessons.group_id')
            ->distinct()
            ->get(['lessons.subject_id', 'groups.licence_id'])
            ->groupBy('subject_id')
            ->map(fn ($rows) => $rows->pluck('licence_id'));

        return Teacher::with(['subjects', 'licences'])->whereKeyNot($teacher->id)->orderBy('last_name')->get()
            ->each(function (Teacher $replacement) use ($licencesBySubject) {
                $licenceIds = $replacement->licences->modelKeys();
                $subjectIds = $licencesBySubject
                    ->filter(fn ($licences, $subjectId) => $replacement->subjects->contains('id', $subjectId)
                        && $licences->diff($licenceIds)->isEmpty())
                    ->keys();

                $replacement->takes = [
                    'all' => $subjectIds->count() === $licencesBySubject->count(),
                    'subjects' => $subjectIds->all(),
                ];
            })
            ->filter(fn (Teacher $replacement) => $replacement->takes['subjects'] !== [])
            ->values();
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['replacement_id' => $message]);
    }
}
