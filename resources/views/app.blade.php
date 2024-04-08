<!DOCTYPE html>
<html lang="en">

    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="shortcut icon" type="image/png" href="{{ asset('/images/logoTecMed.png') }}">
        {{-- <link rel="shortcut icon" sizes="192x192" href="{{ asset('/images/logoTecMed.png') }}"> --}}
        <title>TecMed Documentacion</title>

        @viteReactRefresh

        @vite("resources/app/main.jsx")

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet">

    </head>

    <body>
        <div id="root"></div>
    </body>

</html>