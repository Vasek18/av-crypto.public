<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware{

    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array
     */
    protected $except = [
        //
    ];

    public function handle($request, Closure $next){
        // отключаем проверку csrf в тестах, потому что WithoutMiddleware почему-то ломает подстановку моделей в контроллерах
        if ('testing' !== env('APP_ENV')){
            return parent::handle($request, $next);
        }

        return $next($request);
    }
}
