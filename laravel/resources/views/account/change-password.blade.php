@extends('layouts.main')

@section('content')
    <section data-password-api="{{ url('/api/account/password') }}">
        <h1>{{ $moduleTitle ?? 'Doi mat khau' }}</h1>
        <form method="post" action="{{ url('/api/account/password') }}">
            @csrf
            <label>
                Mat khau hien tai
                <input type="password" name="current_password" autocomplete="current-password">
            </label>
            <label>
                Mat khau moi
                <input type="password" name="new_password" autocomplete="new-password">
            </label>
            <label>
                Xac nhan mat khau moi
                <input type="password" name="new_password_confirmation" autocomplete="new-password">
            </label>
            <button type="submit">Doi mat khau</button>
        </form>
    </section>
@endsection
