<?php

namespace App\Http\Requests;

use App\Models\Venue;
use App\Support\Format;
use Illuminate\Contracts\Validation\ValidationRule;

/** Création et modification d'un site : sa jauge ne descend pas sous celle d'une épreuve qui s'y déroule. */
class VenueRequest extends AdminRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'city' => ['required', 'string', 'max:100'],
            'capacity' => ['required', 'integer', 'max:200000', 'min:'.max(1, $this->biggestEventCapacity())],
        ];
    }

    /**
     * Message dédié seulement quand la limite vient d'une épreuve, sinon le message standard.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        $biggest = $this->biggestEventCapacity();

        return $biggest > 1
            ? ['capacity.min' => 'Une épreuve de ce site prévoit '.Format::number($biggest).' places : la capacité ne peut pas être inférieure.']
            : [];
    }

    private function biggestEventCapacity(): int
    {
        /** @var Venue|null $venue */
        $venue = $this->route('venue');

        return (int) $venue?->events()->max('capacity');
    }
}
