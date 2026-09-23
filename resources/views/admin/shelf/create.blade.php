@extends('admin.layouts.index')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-header bg-dark text-white">
            <h5 class="mb-0">Neues Regal — {{ $lager->name }}</h5>
        </div>

        <div class="card-body">
            <form action="{{ route('admin.shelf.store', $lager) }}" method="POST">
                @csrf

                @include('admin.shelf._form')

                <button type="submit" class="btn btn-filter">Regal erstellen</button>
                <a href="{{ route('admin.shelf.index', $lager) }}" class="btn btn-outline-secondary">Abbrechen</a>
            </form>
        </div>
    </div>
</div>
@endsection