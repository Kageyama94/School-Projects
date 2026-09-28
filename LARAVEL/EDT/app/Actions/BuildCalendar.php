<?php

namespace App\Actions;

use App\Models\Lesson;
use Illuminate\Support\Collection;

class BuildCalendar
{
    /**
     * Génère un fichier iCalendar (.ics) : chaque cours devient un événement hebdomadaire récurrent.
     * Les heures sont « flottantes » (sans fuseau) : l'agenda les affiche à l'heure locale de l'utilisateur.
     *
     * @param  Collection<int, Lesson>  $lessons
     */
    public function handle(Collection $lessons, string $name): string
    {
        $stamp = now()->utc()->format('Ymd\THis\Z');
        $weekStart = now()->startOfWeek();

        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//EDT//Emploi du temps//FR',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.$this->escape($name),
        ];

        foreach ($lessons as $lesson) {
            $date = $weekStart->copy()->addDays($lesson->day_of_week->value - 1)->format('Ymd');
            $details = array_filter([
                $lesson->relationLoaded('teacher') ? $lesson->teacher?->full_name : null,
                $lesson->relationLoaded('group') ? $lesson->group?->label : null,
            ]);

            array_push(
                $lines,
                'BEGIN:VEVENT',
                "UID:lesson-{$lesson->id}@edt",
                "DTSTAMP:{$stamp}",
                "DTSTART:{$date}T{$lesson->start_time->format('Hi')}00",
                "DTEND:{$date}T{$lesson->end_time->format('Hi')}00",
                "RRULE:FREQ=WEEKLY;BYDAY={$lesson->day_of_week->icalCode()}",
                'SUMMARY:'.$this->escape($lesson->subject->name),
            );

            if ($lesson->relationLoaded('room') && $lesson->room) {
                $lines[] = 'LOCATION:'.$this->escape($lesson->room->name);
            }

            if ($details !== []) {
                $lines[] = 'DESCRIPTION:'.$this->escape(implode("\n", $details));
            }

            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return implode("\r\n", array_map($this->fold(...), $lines))."\r\n";
    }

    private function escape(string $text): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n"],
            ['\\\\', '\\;', '\\,', '\\n', '\\n'],
            $text
        );
    }

    /**
     * Les lignes iCalendar ne dépassent pas 75 octets : la suite est repliée sur une ligne commençant par une espace.
     */
    private function fold(string $line): string
    {
        $folded = '';
        $limit = 75;

        while (strlen($line) > $limit) {
            $chunk = mb_strcut($line, 0, $limit, 'UTF-8');
            $folded .= $chunk."\r\n ";
            $line = substr($line, strlen($chunk));
            $limit = 74;
        }

        return $folded.$line;
    }
}
