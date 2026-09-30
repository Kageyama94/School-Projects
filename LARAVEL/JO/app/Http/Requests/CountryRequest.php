<?php

namespace App\Http\Requests;

use App\Models\Country;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

/** Création et modification d'un pays. */
class CountryRequest extends AdminRequest
{
    /** Code CIO en majuscules. Les espaces autour sont déjà retirés par Laravel (middleware TrimStrings). */
    protected function prepareForValidation(): void
    {
        if (is_string($code = $this->input('code'))) {
            $this->merge(['code' => Str::upper($code)]);
        }
    }

    /**
     * Le code CIO est fait de 3 lettres sans accent : la règle « alpha » seule accepterait « été ».
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => ['required', 'alpha:ascii', 'size:3', Rule::unique('countries')->ignore($this->route('country'))],
            'iso' => ['required', Rule::in(array_keys(Country::worldList()))],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return ['code.alpha' => 'Le code CIO ne doit contenir que des lettres sans accent (ex. FRA).'];
    }
}
