<?php

namespace App\Http\Controllers;

use App\Actions\BuildCalendar;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CalendarController extends Controller
{
    /**
     * Exporte l'emploi du temps de l'utilisateur (groupe pour un étudiant, cours pour un enseignant) au format .ics.
     */
    public function __invoke(Request $request, BuildCalendar $buildCalendar): Response
    {
        $user = $request->user();

        if ($user->isStudent() && $user->student?->group) {
            $group = $user->student->group->load('licence');
            $lessons = $group->lessons()->with(['subject', 'teacher', 'room'])->get();
            $name = "Emploi du temps — {$group->label}";
        } elseif ($user->isTeacher() && $user->teacher) {
            $lessons = $user->teacher->lessons()->with(['subject', 'group.licence', 'room'])->get();
            $name = "Emploi du temps — {$user->teacher->full_name}";
        } else {
            abort(404);
        }

        return response($buildCalendar->handle($lessons, $name), 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="emploi-du-temps.ics"',
        ]);
    }
}
