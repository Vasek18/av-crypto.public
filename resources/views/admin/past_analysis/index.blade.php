@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h1>Маркеры и ордера</h1>
                        <hr>
                        <past-analysis :currency_pairs="{{ $currency_pairs }}"
                                       :exchange_markets="{{ $exchange_markets }}"
                                       get_pair_info_action="{{ action('Admin\PastAnalysisController@getPairInfo') }}"
                        ></past-analysis>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
