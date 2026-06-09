@extends('layouts.main')

@section('content')
    <section class="organizer-dashboard">
        <p class="eyebrow">BAN TO CHUC</p>
        <h1>Tong quan ban to chuc</h1>

        <nav aria-label="Ban to chuc">
            <a href="{{ url('/ban-to-chuc/giai-dau') }}">Giai dau</a>
            <a href="{{ url('/ban-to-chuc/lich-thi-dau') }}">Lich thi dau</a>
            <a href="{{ url('/ban-to-chuc/doi-bong') }}">Doi bong</a>
            <a href="{{ url('/ban-to-chuc/san-dau') }}">Quan ly san dau</a>
            <a href="{{ url('/ban-to-chuc/trong-tai') }}">Trong tai</a>
            <a href="{{ url('/ban-to-chuc/ket-qua') }}">Ket qua</a>
            <a href="{{ url('/ban-to-chuc/xep-hang') }}">Xep hang</a>
        </nav>
    </section>
@endsection
