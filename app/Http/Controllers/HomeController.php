<?php

namespace App\Http\Controllers;

use App\Models\ExchangeMarket;
use Illuminate\Support\Facades\Auth;

class HomeController extends Controller
{

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view(
            'home',
            [
                'exchange_markets' => ExchangeMarket::visibleForUser()->with(
                    [
                        'accounts' => function ($query) {
                            $query->where('user_id', Auth::id())->select(
                                [
                                    'id',
                                    'user_id',
                                    'exchange_market_id',
                                    'active',
                                ]
                            );
                        },
                    ]
                )->get(
                    [
                        'id',
                        'name',
                    ]
                ),
            ]
        );
    }
}
