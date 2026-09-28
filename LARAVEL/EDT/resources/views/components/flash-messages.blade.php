@php
    $success = session('success');
    $error = session('error');
    $credentials = session('credentials');
@endphp

@if ($success || $error || $credentials)
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-6 space-y-3 print:hidden">
        @if ($success)
            <x-flash-status>{{ $success }}</x-flash-status>
        @endif

        @if ($credentials)
            <x-flash-status>
                {{ $credentials['message'] }}
                @isset($credentials['identifiant'])
                    Identifiant : <strong>{{ $credentials['identifiant'] }}</strong> —
                @endisset
                mot de passe initial : <strong>{{ $credentials['password'] }}</strong>
                — la personne devra le changer à sa première connexion.
            </x-flash-status>
        @endif

        @if ($error)
            <x-flash-status type="error">{{ $error }}</x-flash-status>
        @endif
    </div>
@endif
