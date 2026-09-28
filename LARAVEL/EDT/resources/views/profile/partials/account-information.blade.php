<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
            {{ __('Informations du compte') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            {{ __('Ces informations sont gérées par un administrateur.') }}
        </p>
    </header>

    <dl class="mt-6 space-y-4 text-gray-700 dark:text-gray-300">
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Identifiant</dt>
            <dd>{{ auth()->user()->identifiant }}</dd>
        </div>
        <div>
            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nom</dt>
            <dd>{{ auth()->user()->name }}</dd>
        </div>
    </dl>
</section>
