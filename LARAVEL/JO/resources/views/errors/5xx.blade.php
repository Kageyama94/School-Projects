@extends('errors.layout')
{{-- Codes sans page dédiée. 500 et 503 gardent la leur : sinon Laravel afficherait ses pages en anglais. --}}

@section('code', $exception->getStatusCode())
@section('title', 'Erreur du serveur')
@section('message', 'Un problème inattendu est survenu de notre côté. Réessayez dans quelques instants.')
