<!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <link rel="stylesheet" href="{{ url('css/app.css') }}">
        <script src="{{ url('js/app.js') }}"></script>
    </head>
    <body>
        @include('components.header')
        <div class="ui grid">
            <div class="three wide column">
                @yield('left')
            </div>
            <div class="ten wide column">
                @yield('content')
            </div>
            <div class="three wide column"></div>
        </div>
    </body>
</html>