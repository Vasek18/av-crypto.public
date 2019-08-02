@extends('layouts.home')

@section('content')
    <div class="home-page full-screen">
        <div class="container-fluid px-5">
            <p class="home-page__paragraph">{!! __('welcome_page.first_screen_page') !!}</p>
        </div>
    </div>
    <div class="graph-wrapper full-screen">
        <div class="container-fluid px-5">
            <h2>{{ __('welcome_page.graph_title') }}</h2>
            <hr>
            <past-analysis :currency_pairs="{{ $currency_pairs }}"
                           :exchange_markets="{{ $exchange_markets }}"
                           get_pair_info_action="{{ action('IndexController@getPairInfo') }}"
            ></past-analysis>
        </div>
    </div>
@endsection