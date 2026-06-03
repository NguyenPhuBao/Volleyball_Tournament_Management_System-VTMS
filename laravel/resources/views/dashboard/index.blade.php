@extends('layouts.main')

@section('content')
    <section>
        <h1>{{ $moduleTitle ?? 'Dashboard' }}</h1>
        <p>Xin chao {{ $user['name'] ?? $user['username'] ?? 'VTMS' }}.</p>
    </section>
@endsection
