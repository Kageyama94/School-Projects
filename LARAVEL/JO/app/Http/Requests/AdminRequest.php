<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/** Base des formulaires d'administration : réservés aux administrateurs, en plus du middleware « admin ». */
abstract class AdminRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }
}
