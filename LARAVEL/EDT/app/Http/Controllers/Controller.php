<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Teacher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

abstract class Controller
{
    protected const PER_PAGE = 10;

    protected function deleteUnlessInUse(Model $model, bool $inUse, string $route, string $inUseMessage, string $deletedMessage): RedirectResponse
    {
        if ($inUse) {
            return redirect()->route($route)->with('error', $inUseMessage);
        }

        $model->delete();

        return redirect()->route($route)->with('success', $deletedMessage);
    }

    protected function deleteWithAccount(Teacher|Student $person): void
    {
        DB::transaction(function () use ($person) {
            $person->delete();
            $person->user?->delete();
        });
    }
}
