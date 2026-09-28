<?php

namespace App\Actions;

use App\Enums\DayOfWeek;
use App\Models\Group;
use App\Models\Lesson;
use App\Models\Licence;
use App\Models\Room;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;

class BuildTeacherSchedule
{
    /**
     * Données du tableau de bord d'un enseignant : ses cours, la grille hebdomadaire et la configuration
     * du composant Alpine (groupes, salles et créneaux déjà occupés).
     */
    public function handle(?Teacher $teacher): array
    {
        $groups = Group::with('licence')->withCount('students')->ordered()->get();
        $slots = Lesson::timeSlots();
        $days = DayOfWeek::schoolDays();

        $lessons = $teacher
            ? $teacher->lessons()->with(['subject', 'group.licence', 'room'])->get()
            : new EloquentCollection;

        return [
            'teacher' => $teacher,
            'subjects' => $teacher?->subjects()->orderBy('name')->get() ?? collect(),
            'groups' => $groups,
            'slots' => $slots,
            'days' => $days,
            'teacherByCell' => $lessons->keyBy(fn (Lesson $lesson) => $lesson->cellKey()),
            'myLessons' => $lessons->sortBy(fn (Lesson $lesson) => $lesson->start_time->format('H:i'))->values(),
            'slotsByDay' => collect($days)->mapWithKeys(fn (DayOfWeek $day) => [$day->value => Lesson::slotsForDay($day->value)]),
            'scheduler' => $this->schedulerConfig($groups, $slots, $days),
        ];
    }

    /**
     * @param  Collection<int, Group>  $groups
     * @param  array<int, array{start: string, end: string}>  $slots
     * @param  array<int, DayOfWeek>  $days
     */
    private function schedulerConfig(Collection $groups, array $slots, array $days): array
    {
        $rooms = Room::orderBy('name')->get();

        // Créneaux occupés de tous les cours : lignes brutes, sans instancier un modèle par cours (des milliers).
        $lessonRows = Lesson::query()->toBase()->get(['group_id', 'room_id', 'day_of_week', 'start_time']);

        return [
            'slots' => $slots,
            'dayLabels' => collect($days)->mapWithKeys(fn (DayOfWeek $day) => [$day->value => $day->label()]),
            'licences' => $groups->pluck('licence')->unique('id')->map(fn (Licence $licence) => [
                'id' => $licence->id,
                'name' => $licence->name,
            ])->values(),
            'groups' => $groups->map(fn (Group $group) => [
                'id' => $group->id,
                'licence_id' => $group->licence_id,
                'level' => $group->level->short(),
                'name' => $group->name,
                'label' => $group->label,
                'size' => $group->students_count,
            ])->values(),
            'groupBusyCells' => $this->busyCellsBy($lessonRows, 'group_id'),
            'rooms' => $rooms->map(fn (Room $room) => [
                'id' => $room->id,
                'capacity' => $room->capacity,
                'label' => $room->name.' ('.$room->type->label().($room->capacity ? ', '.$room->capacity.' places' : '').')',
            ])->values(),
            'roomBusyCells' => $this->busyCellsBy($lessonRows, 'room_id'),
        ];
    }

    /**
     * @param  Collection<int, object>  $lessonRows
     * @return Collection<int, Collection<int, string>>
     */
    private function busyCellsBy(Collection $lessonRows, string $column): Collection
    {
        return $lessonRows->whereNotNull($column)
            ->groupBy($column)
            ->map(fn ($rows) => $rows->map(fn ($row) => Lesson::cellKeyFor($row->day_of_week, $row->start_time))->values());
    }
}
