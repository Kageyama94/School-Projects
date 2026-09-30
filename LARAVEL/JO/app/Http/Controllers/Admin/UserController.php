<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\UserRoleRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => 'nullable|string|max:100',
            'role' => ['nullable', Rule::enum(Role::class)],
        ]);

        $users = User::withSum(['tickets' => fn (Builder $query) => $query->active()], 'quantity')
            ->when($filters['q'] ?? null, fn (Builder $query, string $search) => $query->where(fn (Builder $query) => $query
                ->where('name', 'like', "%$search%")
                ->orWhere('email', 'like', "%$search%")))
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->where('role', $role))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.users.index', ['users' => $users, 'filters' => $filters]);
    }

    /** Le rôle est hors $fillable : seule cette action le change (voir UserRoleRequest). */
    public function update(UserRoleRequest $request, User $user): RedirectResponse
    {
        $user->forceFill(['role' => $request->validated('role')])->save();

        return back()->with('success', "{$user->name} est maintenant ".mb_strtolower($user->role->label()).'.');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($user->is($request->user())) {
            return back()->withErrors(['user' => 'Supprimez votre propre compte depuis la page « Mon profil ».']);
        }

        $user->delete();

        return back()->with('success', "Le compte de {$user->name} a été supprimé.");
    }
}
