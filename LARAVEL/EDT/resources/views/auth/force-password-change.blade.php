<x-guest-layout title="Nouveau mot de passe">
    <div class="mb-4 text-sm text-gray-600 dark:text-gray-400">
        {{ __('Pour des raisons de sécurité, tu dois choisir un nouveau mot de passe avant de continuer.') }}
    </div>

    <form method="POST" action="{{ route('password.force-change') }}">
        @csrf
        @method('put')

        <div>
            <x-input-label for="password" :value="__('Nouveau mot de passe')" />
            <x-text-input id="password" class="block mt-1 w-full"
                            type="password"
                            name="password"
                            required autofocus autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                            type="password"
                            name="password_confirmation"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div class="flex justify-end mt-4">
            <x-primary-button>
                {{ __('Changer le mot de passe') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
