<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pizzeria</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>

<header><a href="{{ url('/pizzeria') }}" style="color:white; text-decoration:none;">🍕 Pizzeria</a></header>

<div class="container">
    @yield('content')
</div>

</body>
</html>
