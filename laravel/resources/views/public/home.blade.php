@extends('layouts.main')

@section('content')
    <section>
        <h1>VTMS</h1>
        <p>He thong quan ly giai dau bong chuyen.</p>
        @if (!empty($user))
            <a href="{{ url('/dashboard') }}">Vao dashboard</a>
        @else
            <a href="{{ url('/login') }}">Dang nhap</a>
        @endif
    </section>
@endsection
