<?php

namespace App\Http\Controllers;

use App\Enums\RoomType;
use App\Models\Room;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminRoomController extends Controller
{
    public function index(): View
    {
        return view('admin.rooms.index', [
            'rooms' => Room::withCount('lessons')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.rooms.form', ['room' => new Room, 'types' => RoomType::cases()]);
    }

    public function store(Request $request): RedirectResponse
    {
        Room::create($request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:rooms'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'type' => ['required', Rule::enum(RoomType::class)],
        ]));

        return redirect()->route('admin.rooms.index')->with('success', 'Salle créée.');
    }

    public function show(Room $room): View
    {
        return view('admin.timetable', [
            'title' => "Emploi du temps — {$room->name}",
            'back' => route('admin.rooms.index'),
            'lessons' => $room->lessons()->with(['subject', 'group.licence', 'teacher'])->orderBy('start_time')->get(),
        ]);
    }

    public function edit(Room $room): View
    {
        return view('admin.rooms.form', ['room' => $room, 'types' => RoomType::cases()]);
    }

    public function update(Request $request, Room $room): RedirectResponse
    {
        $room->update($request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('rooms')->ignore($room)],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'type' => ['required', Rule::enum(RoomType::class)],
        ]));

        return redirect()->route('admin.rooms.index')->with('success', 'Salle mise à jour.');
    }

    public function destroy(Room $room): RedirectResponse
    {
        return $this->deleteUnlessInUse(
            $room,
            $room->lessons()->exists(),
            'admin.rooms.index',
            'Cette salle est utilisée par des cours : impossible de la supprimer.',
            'Salle supprimée.',
        );
    }
}
