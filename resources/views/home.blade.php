@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h1>{{ Auth::user()->name }}</h1>
                        <hr>
                        <user-exchange-markets :exchange_markets="{{ $exchange_markets }}"></user-exchange-markets>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
