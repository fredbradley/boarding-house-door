<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Door Display</title>
    @vite(['resources/css/app.css'])
    <style>
        html, body { height: 100%; margin: 0; overflow: hidden; }
    </style>
</head>
<body class="bg-slate-950">
    {{ $slot }}
</body>
</html>
