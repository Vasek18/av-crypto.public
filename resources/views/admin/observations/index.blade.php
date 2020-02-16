@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h1>Наблюдения</h1>
                        <hr>
                        <currency-pair-select :currency_pairs="{{ $currency_pairs }}"
                                              :exchange_markets="{{ $exchange_markets }}"
                                              :selected_pair_id="{{ app('request')->input('currency_pair')?:0 }}"
                        ></currency-pair-select>
                        <hr>
                        @if ($observations)
                            <observations-list :observations="{{ $observations }}"
                                               :percent="{{ $percent }}"></observations-list>

                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
