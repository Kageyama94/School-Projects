<?php

namespace App\Models;

use App\Enums\DayOfWeek;
use Database\Factories\LessonFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Lesson extends Model
{
    /** @use HasFactory<LessonFactory> */
    use HasFactory;

    protected $fillable = [
        'group_id',
        'subject_id',
        'teacher_id',
        'room_id',
        'day_of_week',
        'start_time',
        'end_time',
    ];

    protected function casts(): array
    {
        return [
            'day_of_week' => DayOfWeek::class,
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(Group::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(Teacher::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    /**
     * Clé d'un créneau (jour + heure de début), par exemple « 2-09:00 ».
     */
    public static function cellKeyFor(int $day, string $start): string
    {
        return $day.'-'.substr($start, 0, 5);
    }

    public function cellKey(): string
    {
        return static::cellKeyFor($this->day_of_week->value, $this->start_time->format('H:i'));
    }

    /**
     * @return array<int, array{start: string, end: string}>
     */
    public static function timeSlots(): array
    {
        return [
            ['start' => '08:00', 'end' => '09:00'],
            ['start' => '09:00', 'end' => '10:00'],
            ['start' => '10:00', 'end' => '11:00'],
            ['start' => '11:00', 'end' => '12:00'],
            ['start' => '14:00', 'end' => '15:00'],
            ['start' => '15:00', 'end' => '16:00'],
            ['start' => '16:00', 'end' => '17:00'],
            ['start' => '17:00', 'end' => '18:00'],
        ];
    }

    /**
     * Créneaux disponibles pour un jour donné (le samedi s'arrête à midi).
     *
     * @return array<int, array{start: string, end: string}>
     */
    public static function slotsForDay(int $day): array
    {
        $slots = static::timeSlots();

        if ($day !== DayOfWeek::Saturday->value) {
            return $slots;
        }

        return array_filter($slots, fn ($slot) => $slot['start'] < '12:00');
    }

    /**
     * Indique si un enseignant, un groupe ou une salle a déjà un cours qui chevauche ce créneau.
     *
     * @param  'teacher_id'|'group_id'|'room_id'  $column
     */
    public static function overlapsFor(string $column, int $id, int $dayOfWeek, string $start, string $end): bool
    {
        return static::query()
            ->where($column, $id)
            ->where('day_of_week', $dayOfWeek)
            ->where('start_time', '<', $end)
            ->where('end_time', '>', $start)
            ->exists();
    }
}
