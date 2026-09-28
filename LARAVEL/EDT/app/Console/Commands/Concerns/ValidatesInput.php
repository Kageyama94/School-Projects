<?php

namespace App\Console\Commands\Concerns;

use Illuminate\Support\Facades\Validator;

trait ValidatesInput
{
    /**
     * Valide les données ; affiche chaque erreur et renvoie null en cas d'échec.
     *
     * @param  array<string, string>  $attributes
     */
    private function validateOrFail(array $data, array $rules, array $attributes = []): ?array
    {
        $validator = Validator::make($data, $rules, [], $attributes);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return null;
        }

        return $validator->validated();
    }
}
