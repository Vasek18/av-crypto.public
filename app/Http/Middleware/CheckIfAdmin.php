<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class CheckIfAdmin{

    public function handle($request, Closure $next, $guard = null){
        $user = $request->user();
        if ($user){
            if ($user->isAdmin()){
                return $next($request);
            }
        }

        if ($request->ajax()){
            return response('Unauthorized.', 401);
        } else{
            return redirect()->guest(route('login'));
        }
    }
}
