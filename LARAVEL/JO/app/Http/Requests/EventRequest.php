<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\Event;
use App\Models\Venue;
use App\Support\Format;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Création et modification d'une épreuve (administration). */
class EventRequest extends AdminRequest
{
    /** Case à cocher : absente du formulaire quand elle n'est pas cochée. */
    protected function prepareForValidation(): void
    {
        $this->merge(['team' => $this->boolean('team')]);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'sport_id' => ['required', 'exists:sports,id'],
            'venue_id' => ['required', 'exists:venues,id'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'starts_at' => ['required', 'date'],
            'price' => ['required', 'integer', 'min:0', 'max:10000'],
            'capacity' => ['required', 'integer', 'min:1', 'max:200000'],
            'team' => ['boolean'],
        ];
    }

    /**
     * Règles qui dépendent de la base : jauge du site, billets déjà vendus, résultats déjà saisis.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $this->checkCapacity($validator);
            $this->checkEventWithResults($validator);
        }];
    }

    /** L'épreuve modifiée, ou null à la création. */
    private function event(): ?Event
    {
        return $this->route('event');
    }

    /** Pas plus de places que le site, ni moins que les billets déjà vendus (une épreuve annulée n'en a plus). */
    private function checkCapacity(Validator $validator): void
    {
        $venue = Venue::find($this->integer('venue_id'));
        $capacity = $this->integer('capacity');
        $event = $this->event();
        $sold = $event && ! $event->isCancelled() ? $event->seatsSold() : 0;

        if ($capacity > $venue->capacity) {
            $validator->errors()->add('capacity', "Le site {$venue->name} ne compte que ".Format::number($venue->capacity).' places.');
        } elseif ($capacity < $sold) {
            $validator->errors()->add('capacity', Format::number($sold).' billets sont déjà vendus : la capacité ne peut pas être inférieure.');
        }
    }

    /** Une épreuve qui a des résultats garde son sport, sa catégorie, son type, et reste dans le passé. */
    private function checkEventWithResults(Validator $validator): void
    {
        $event = $this->event();
        if (! $event?->results()->exists()) {
            return;
        }

        $identityChanged = $this->integer('sport_id') !== $event->sport_id
            || $this->enum('gender', Gender::class) !== $event->gender
            || $this->boolean('team') !== $event->team;

        if ($identityChanged) {
            $validator->errors()->add('sport_id', "Des résultats sont saisis : effacez-les avant de changer le sport, la catégorie ou le type d'épreuve.");
        }
        if (Carbon::parse($this->input('starts_at'))->isFuture()) {
            $validator->errors()->add('starts_at', "Des résultats sont saisis : l'épreuve ne peut pas être déplacée dans le futur.");
        }
    }
}
