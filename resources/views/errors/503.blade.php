@extends('errors::layout')

@section('title', 'Service Unavailable')

@section('content')
<!-- Error -->
<div class="container-xxl container-p-y">
    <div class="misc-wrapper">
        <h2 class="mb-2 mx-2">Service Unavailable</h2>
        <p class="mb-4 mx-2">Oops! 503 😖 The requested URL was not found on this server.</p>
        <a href="{{ url()->previous() }}" class="btn btn-primary">Go to back</a>
        <div class="mt-3">
            <img src="{{ asset('admin') }}/img/illustrations/page-misc-error-light.png" alt="page-misc-error-light" width="500" class="img-fluid" data-app-dark-img="{{ asset('admin') }}/illustrations/page-misc-error-dark.png" data-app-light-img="illustrations/page-misc-error-light.png" />
        </div>
    </div>
</div>
<!-- /Error -->
@endsection
