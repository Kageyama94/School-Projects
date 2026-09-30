<?php

namespace App\Http\Requests;

use App\Enums\Gender;
use App\Models\Athlete;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Création et modification d'un athlète. */
class AthleteRequest extends AdminRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(Gender::class)->only(Gender::forAthletes())],
            'sport_id' => ['required', 'exists:sports,id'],
            'country_id' => ['required', 'exists:countries,id'],
        ];
    }

    /**
     * Un médaillé garde son sport, son genre et son pays, sinon ses podiums deviendraient incohérents.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            /** @var Athlete|null $athlete */
            $athlete = $this->route('athlete');
            if (! $athlete || $validator->errors()->isNotEmpty()) {
                return;
            }

            $identityChanged = $this->integer('sport_id') !== $athlete->sport_id
                || $this->input('gender') !== $athlete->gender->value
                || $this->integer('country_id') !== $athlete->country_id;

            if ($identityChanged && $athlete->results()->exists()) {
                $validator->errors()->add('sport_id', 'Cet athlète a des médailles : son sport, son genre et son pays ne peuvent plus changer.');
            }
        }];
    }
}
