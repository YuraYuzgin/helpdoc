<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\HeaderJsonTypeValidator;

class _CheckHeaderType 
{
    public function handle($request, Closure $next){
        // валидируем тип заголовка запроса
        $result = HeaderJsonTypeValidator::validate_json_header($request);
        if ($result !== true){ return $result; }

        return $next($request);
    }
}
