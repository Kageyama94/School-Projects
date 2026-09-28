<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLessonRequest;
use App\Models\Lesson;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LessonController extends Controller
{
    public function store(StoreLessonRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $slot = Lesson::timeSlots()[$validated['slot']];

        try {
            Lesson::create([
                'group_id' => $validated['group_id'],
                'subject_id' => $validated['subject_id'],
                'teacher_id' => $request->user()->teacher->id,
                'room_id' => $validated['room_id'] ?? null,
                'day_of_week' => $validated['day_of_week'],
                'start_time' => $slot['start'],
                'end_time' => $slot['end'],
            ]);
        } catch (UniqueConstraintViolationException) {
            // Une réservation simultanée a pris le créneau entre la validation et l'insertion.
            throw ValidationException::withMessages(['slot' => 'Ce créneau vient d\'être pris, choisissez-en un autre.']);
        }

        return back()->with('success', 'Cours ajouté.')->withInput($request->only('group_id'));
    }

    public function destroy(Request $request, Lesson $lesson): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user->isAdmin() || $lesson->teacher_id === $user->teacher?->id, 403);

        $lesson->delete();

        return back()->with('success', 'Cours retiré.')->withInput($request->only('group_id'));
    }
}
