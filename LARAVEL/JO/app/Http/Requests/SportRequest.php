<?php

namespace App\Http\Requests;

use App\Models\Sport;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Création et modification d'un sport. */
class SportRequest extends AdminRequest
{
    /** Case à cocher : absente du formulaire quand elle n'est pas cochée. */
    protected function prepareForValidation(): void
    {
        $this->merge(['two_bronzes' => $this->boolean('two_bronzes')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('sports')->ignore($this->route('sport'))],
            'icon' => ['required', 'string', 'max:16'],
            'description' => ['nullable', 'string', 'max:500'],
            'two_bronzes' => ['boolean'],
        ];
    }

    /**
     * Retirer l'option « deux bronzes » alors qu'une épreuve en a déjà deux laisserait un podium impossible.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Sport|null $sport */
            $sport = $this->route('sport');

            if ($sport && ! $this->boolean('two_bronzes') && $sport->hasEventWithTwoBronzes()) {
                $validator->errors()->add('two_bronzes', "Une épreuve de ce sport a déjà deux médailles de bronze : corrigez ses résultats d'abord.");
            }
        }];
    }
}
