<?php

namespace App\Http\Requests;

use App\Enums\DayOfWeek;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\Student;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLessonRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->teacher !== null;
    }

    public function rules(): array
    {
        $teacherSubjectIds = $this->user()->teacher->subjects()->pluck('subjects.id')->all();

        return [
            'group_id' => ['required', 'exists:groups,id'],
            'subject_id' => ['required', Rule::in($teacherSubjectIds)],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'day_of_week' => ['required', 'integer', Rule::in(array_column(DayOfWeek::schoolDays(), 'value'))],
            'slot' => ['required', 'integer', Rule::in(array_keys(Lesson::timeSlots()))],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->hasAny(['group_id', 'subject_id', 'room_id', 'day_of_week', 'slot'])) {
                    return;
                }

                $teacher = $this->user()->teacher;
                $day = $this->integer('day_of_week');
                $slot = Lesson::timeSlots()[$this->integer('slot')];
                [$start, $end] = [$slot['start'], $slot['end']];

                if (! array_key_exists($this->integer('slot'), Lesson::slotsForDay($day))) {
                    $validator->errors()->add('slot', 'Le samedi, les cours ont lieu uniquement le matin (avant 12h).');

                    return;
                }

                if (Lesson::overlapsFor('teacher_id', $teacher->id, $day, $start, $end)) {
                    $validator->errors()->add('slot', 'Vous avez déjà un cours sur ce créneau.');

                    return;
                }

                if (Lesson::overlapsFor('group_id', $this->integer('group_id'), $day, $start, $end)) {
                    $validator->errors()->add('group_id', 'Ce groupe a déjà un cours sur ce créneau.');

                    return;
                }

                $room = $this->filled('room_id') ? Room::find($this->integer('room_id')) : null;

                if ($room && Lesson::overlapsFor('room_id', $room->id, $day, $start, $end)) {
                    $validator->errors()->add('room_id', 'Cette salle est déjà occupée sur ce créneau.');

                    return;
                }

                $groupSize = Student::where('group_id', $this->integer('group_id'))->count();

                if ($room && $room->capacity !== null && $room->capacity < $groupSize) {
                    $validator->errors()->add('room_id', "Cette salle ({$room->capacity} places) est trop petite pour ce groupe ({$groupSize} étudiants).");
                }
            },
        ];
    }
}
