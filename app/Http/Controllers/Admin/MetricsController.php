<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Metrics\Metrics;
use Illuminate\Http\Request;

class MetricsController extends Controller{

    /**
     * @return void
     */
    public function __construct(){

    }

    /**
     * @return \Illuminate\Http\Response
     */
    public function index(){
        $metrics = Metrics::orderBy('sort')->get();

        return view(
            'admin.metrics.index',
            [
                'metrics' => $metrics
            ]
        );
    }
}