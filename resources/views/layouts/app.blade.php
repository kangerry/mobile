<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Backoffice' }} - KOMERA</title>
    <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" integrity="sha512-+fQvB9..." crossorigin="anonymous" referrerpolicy="no-referrer" />
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>body{font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Helvetica, Arial, "Apple Color Emoji","Segoe UI Emoji";}</style>
    @yield('styles')
</head>
<body>
<div class="layout">
    @include('partials.sidebar')
    @include('partials.navbar')
    <main class="content">
        @if(session('status'))
            <div class="card toast" style="margin-bottom: 12px;">
                {{ session('status') }}
            </div>
        @endif
        @yield('content')
    </main>
    @include('partials.footer')
    </div>
    <script src="{{ asset('assets/js/app.js') }}"></script>
</body>
</html>
