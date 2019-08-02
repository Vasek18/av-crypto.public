@extends('layouts.app')

@section('content')
    <div class="container">
        <h1>Логи</h1>
        <div class="row">
            <div class="col-md-12">
                @foreach($logs as $log)
                    <div class="card mb-2">
                        <div class="card-body">
                            <div class="form-group">
                                <textarea name="log"
                                          class="form-control"
                                          rows="10"
                                >{{ $log }}
                                </textarea>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endsection