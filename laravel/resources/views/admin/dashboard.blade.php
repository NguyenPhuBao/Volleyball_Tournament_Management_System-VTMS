@extends('layouts.main')

@section('content')
    <section class="admin-dashboard">
        <p class="eyebrow">ADMIN</p>
        <h1>Tong quan quan tri</h1>

        <nav aria-label="Admin">
            <a href="{{ url('/admin/users') }}">Quan tri tai khoan</a>
            <a href="{{ url('/admin/nguoi-dung') }}">Ho so nguoi dung</a>
            <a href="{{ url('/admin/logs') }}">Nhat ky he thong</a>
            <a href="{{ url('/admin/xac-nhan-thong-tin-btc') }}">Xac nhan thong tin ban to chuc</a>
        </nav>
    </section>
@endsection
