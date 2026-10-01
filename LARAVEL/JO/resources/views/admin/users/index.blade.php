@extends('layouts.admin')

@section('title', 'Utilisateurs')
@section('heading', 'Utilisateurs')
@section('subheading', $users->total().' comptes')

@section('body')
<form method="GET" class="filters filters-3 card">
    <label>
        Recherche
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Nom ou e-mail">
    </label>
    <label>
        Rôle
        <select name="role">
            <option value="">Tous</option>
            @foreach (\App\Enums\Role::cases() as $role)
                <option value="{{ $role->value }}" @selected(($filters['role'] ?? null) === $role->value)>{{ $role->label() }}s</option>
            @endforeach
        </select>
    </label>
    <div class="filters-actions">
        <button class="btn btn-primary">Filtrer</button>
        <a href="{{ route('admin.users.index') }}" class="btn btn-ghost-dark">Réinitialiser</a>
    </div>
</form>

<div class="card table-card list-gap">
    <table class="table">
        <thead>
            <tr><th>Nom</th><th>E-mail</th><th class="center">Billets</th><th>Rôle</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                @php $isMe = $user->is(auth()->user()); @endphp
                <tr>
                    <td>{{ $user->name }} @if ($isMe)<span class="muted small">(vous)</span>@endif</td>
                    <td class="muted">{{ $user->email }}</td>
                    <td class="center">{{ (int) $user->tickets_sum_quantity ?: '—' }}</td>
                    <td>
                        @if ($isMe)
                            <span class="tag tag-admin">Administrateur</span>
                        @else
                            {{-- Bouton explicite (changer la liste au clavier ne soumet rien) et confirmation avant l'envoi. --}}
                            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="inline-form"
                                  onsubmit="return confirm(this.role.value === @js(\App\Enums\Role::Admin->value) ? @js('Nommer '.$user->name.' administrateur ?') : @js('Retirer les droits d\'administrateur de '.$user->name.' ?'))">
                                @csrf
                                @method('PUT')
                                <select name="role" aria-label="Rôle de {{ $user->name }}">
                                    @foreach (\App\Enums\Role::cases() as $role)
                                        <option value="{{ $role->value }}" @selected($user->role === $role)>{{ $role->label() }}</option>
                                    @endforeach
                                </select>
                                <button class="btn btn-sm btn-ghost-dark">Enregistrer</button>
                            </form>
                        @endif
                    </td>
                    <td class="right nowrap actions">
                        @unless ($isMe)
                            @include('partials.delete-button', [
                                'action' => route('admin.users.destroy', $user),
                                'confirm' => 'Supprimer le compte de '.$user->name.' ? Ses billets à venir seront annulés.',
                            ])
                        @endunless
                    </td>
                </tr>
            @empty
                <x-empty-row colspan="5">Aucun compte ne correspond.</x-empty-row>
            @endforelse
        </tbody>
    </table>
</div>

{{ $users->links('partials.pagination') }}
@endsection
