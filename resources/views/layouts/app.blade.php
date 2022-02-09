<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
@include('layouts.head')
<body class="hold-transition sidebar-mini">
<div class="wrapper">
    @include('layouts.navbar')
    @include('layouts.aside')
        <div class="content-wrapper">
            @yield('content')
        </div>
    @include('layouts.footer')
    </div>
    @include('layouts.script')
</body>
</html>
