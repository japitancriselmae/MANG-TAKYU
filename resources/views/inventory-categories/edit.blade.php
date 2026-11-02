@extends('layouts.app')

@section('title', 'Edit category')
@section('page-title', 'Edit category')
@section('page-subtitle', 'Update this category without changing its inventory.')

@section('content')
    <section class="mx-auto max-w-2xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm sm:p-8">
        @include('inventory-categories._form', ['action' => route('inventory-categories.update', $inventoryCategory), 'method' => 'PUT', 'category' => $inventoryCategory])
    </section>
@endsection
