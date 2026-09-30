@extends('errors.layout')
{{-- Codes sans page dédiée. 403, 404, 419 et 429 gardent la leur : sinon Laravel afficherait ses pages en anglais. --}}

@section('code', $exception->getStatusCode())
@section('title', 'Requête impossible')
@section('message', "Cette demande n'a pas pu être traitée. Revenez en arrière et réessayez.")
