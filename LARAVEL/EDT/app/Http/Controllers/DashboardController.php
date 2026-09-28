<?php

namespace App\Http\Controllers;

use App\Actions\BuildTeacherSchedule;
use App\Models\Group;
use App\Models\Room;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, BuildTeacherSchedule $buildTeacherSchedule): View
    {
        $user = $request->user();

        if ($user->isStudent()) {
            $group = $user->student?->group?->load('licence');
            $lessons = $group
                ? $group->lessons()->with(['subject', 'teacher', 'room'])->orderBy('start_time')->get()
                : collect();

            return view('dashboard.student', [
                'group' => $group,
                'lessons' => $lessons,
            ]);
        }

        if ($user->isTeacher()) {
            return view('dashboard.teacher', $buildTeacherSchedule->handle($user->teacher));
        }

        return view('dashboard.admin', [
            'counts' => [
                'groups' => Group::count(),
                'subjects' => Subject::count(),
                'teachers' => Teacher::count(),
                'students' => Student::count(),
                'rooms' => Room::count(),
                'students_without_group' => Student::whereNull('group_id')->count(),
            ],
        ]);
    }
}
