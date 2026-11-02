@extends('layouts.app')

@section('title', 'Add category')
@section('page-title', 'Add category')
@section('page-subtitle', 'Create a category for your tracked ingredients.')

@section('content')
    <section class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @include('inventory-categories._form', ['action' => route('inventory-categories.store'), 'method' => 'POST', 'category' => null])
    </section>
@endsection
