<?php

namespace App\Http\Requests;

use App\Enums\Role;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

/** Nommer ou retirer un administrateur. On ne change pas son propre rôle, pour ne pas s'enfermer dehors. */
class UserRoleRequest extends AdminRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return ['role' => ['required', Rule::enum(Role::class)]];
    }

    /**
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->route('user')->is($this->user())) {
                $validator->errors()->add('user', 'Vous ne pouvez pas modifier votre propre rôle.');
            }
        }];
    }
}
