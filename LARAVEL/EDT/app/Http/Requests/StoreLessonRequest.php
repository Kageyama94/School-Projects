<?php

namespace App\Http\Requests;

use App\Enums\DayOfWeek;
use App\Models\Lesson;
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
        $teacher = $this->user()->teacher;
        $teacherSubjectIds = $teacher->subjects()->pluck('subjects.id')->all();
        $teacherLicenceIds = $teacher->licences()->pluck('licences.id')->all();

        return [
            // Un enseignant ne programme que dans les groupes des licences qui lui sont assignées.
            'group_id' => ['required', Rule::exists('groups', 'id')->whereIn('licence_id', $teacherLicenceIds)],
            'subject_id' => ['required', Rule::in($teacherSubjectIds)],
            'room_id' => ['nullable', 'exists:rooms,id'],
            'day_of_week' => ['required', 'integer', Rule::in(array_column(DayOfWeek::schoolDays(), 'value'))],
            'slot' => ['required', 'integer', Rule::in(array_keys(Lesson::timeSlots()))],
        ];
    }

    public function messages(): array
    {
        return [
            'group_id.exists' => 'Ce groupe ne fait pas partie de tes licences.',
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

                if ($this->filled('room_id') && Lesson::overlapsFor('room_id', $this->integer('room_id'), $day, $start, $end)) {
                    $validator->errors()->add('room_id', 'Cette salle est déjà occupée sur ce créneau.');
                }
            },
        ];
    }
}
