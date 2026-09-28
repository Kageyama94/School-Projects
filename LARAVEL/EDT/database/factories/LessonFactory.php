<?php

namespace Database\Factories;

use App\Models\Group;
use App\Models\Lesson;
use App\Models\Room;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lesson>
 */
class LessonFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $slot = fake()->randomElement(Lesson::timeSlots());

        return [
            'group_id' => Group::factory(),
            'subject_id' => Subject::factory(),
            'teacher_id' => Teacher::factory(),
            'room_id' => Room::factory(),
            'day_of_week' => fake()->numberBetween(1, 6),
            'start_time' => $slot['start'],
            'end_time' => $slot['end'],
        ];
    }
}
