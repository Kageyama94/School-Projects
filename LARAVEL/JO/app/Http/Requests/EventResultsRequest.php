<?php

namespace App\Http\Requests;

use App\Models\Athlete;
use App\Models\Country;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/**
 * Podium d'une épreuve : athlètes (ou pays pour une épreuve par équipes) éligibles, sans trou
 * (pas d'argent sans or, pas de bronze sans argent), et au plus une médaille par médaillé.
 */
class EventResultsRequest extends AdminRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $event = $this->event();
        $eligible = Rule::in(($event->team ? Country::eligibleFor($event) : Athlete::eligibleFor($event))->pluck('id')->all());

        return [
            'gold' => ['nullable', 'required_with:silver,bronze,bronze_2', $eligible],
            'silver' => ['nullable', 'required_with:bronze,bronze_2', $eligible],
            'bronze' => ['nullable', 'required_with:bronze_2', $eligible],
            'bronze_2' => array_key_exists('bronze_2', $event->medalSlots()) ? ['nullable', $eligible] : ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'gold.required_with' => "Attribuez la médaille d'or avant les suivantes.",
            'silver.required_with' => "Attribuez la médaille d'argent avant le bronze.",
            'bronze.required_with' => 'Remplissez le premier bronze avant le second.',
        ];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $event = $this->event();

            if ($reason = $event->resultsLockedReason()) {
                $validator->errors()->add('gold', $reason);

                return;
            }

            $picked = array_filter($this->only(array_keys($event->medalSlots())));
            if (count($picked) !== count(array_unique($picked))) {
                $who = $event->team ? 'Un pays' : 'Un athlète';
                $validator->errors()->add('gold', "$who ne peut recevoir qu'une seule médaille par épreuve.");
            }
        }];
    }

    private function event(): Event
    {
        return $this->route('event')->loadMissing('sport');
    }
}
