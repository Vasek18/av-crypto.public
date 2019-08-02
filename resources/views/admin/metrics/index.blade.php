@extends('layouts.app')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <h1>Метрики</h1>
                        <div class="row">
                            @foreach($metrics as $metric)
                                @if ($metric->last_value)
                                    <div class="col-md-6 metric-wrapper">
                                        <div class="jumbotron metric metrics__item">
                                            <p class="h1 metric__current-value">{{ round($metric->last_value->value, 3) }}
                                                <span class="small metric__current-value-date">({{ $metric->last_value->day }})</span>
                                            </p>
                                            <p class="metric__name">{{ $metric['name'] }}</p>
                                        </div>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection