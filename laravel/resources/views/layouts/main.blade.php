<!doctype html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'VTMS' }}</title>
    @foreach (($styles ?? []) as $style)
        <link rel="stylesheet" href="{{ asset('assets/'.ltrim($style, '/')) }}">
    @endforeach
</head>
<body>
    <header>
        <a href="{{ url('/dashboard') }}">VTMS</a>
        @if (!empty($user))
            <form method="post" action="{{ url('/logout') }}">
                @csrf
                <button type="submit">Dang xuat</button>
            </form>
        @else
            <a href="{{ url('/login') }}">Dang nhap</a>
        @endif
    </header>

    <main>
        @yield('content')
    </main>

    @foreach (($scripts ?? []) as $script)
        <script src="{{ asset('assets/'.ltrim($script, '/')) }}"></script>
    @endforeach
</body>
</html>
