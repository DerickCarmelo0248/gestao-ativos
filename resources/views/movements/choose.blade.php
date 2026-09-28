@extends('layouts.app')
@section('title', $movement === 'entry' ? 'Entrada' : 'Saída')
@section('content')
<div class="module-page">
    @include('movements.selector')
</div>
@endsection
