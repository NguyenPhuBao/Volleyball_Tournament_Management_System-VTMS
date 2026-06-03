@extends('layouts.auth')

@section('content')
    <main>
        <h1>Dang nhap</h1>

        @if (!empty($error))
            <p>{{ $error }}</p>
        @endif

        <form method="post" action="{{ url('/login') }}">
            @csrf
            <label>
                Ten dang nhap
                <input name="username" autocomplete="username">
            </label>
            <label>
                Mat khau
                <input name="password" type="password" autocomplete="current-password">
            </label>
            <button type="submit">Dang nhap</button>
        </form>
    </main>
@endsection
