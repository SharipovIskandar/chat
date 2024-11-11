<!-- resources/views/layouts/app.blade.php -->

<!DOCTYPE html>
<html lang="uz">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Chat</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    @yield('styles')
</head>
<body>
<div class="flex h-screen">
    @yield('sidebar')
    <div class="flex-1">
        @yield('content')
    </div>
</div>

@yield('scripts')
</body>
</html>
