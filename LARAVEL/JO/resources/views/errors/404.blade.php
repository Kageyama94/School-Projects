@extends('errors.layout')

@section('code', '404')
@section('title', "Page introuvable")
@section('message', "Cette page n'existe pas ou a été supprimée. Vérifiez l'adresse, ou repartez du calendrier des épreuves.")

@section('actions')
    <a href="{{ url('/epreuves') }}" class="btn btn-ghost-dark">Voir les épreuves</a>
@endsection
