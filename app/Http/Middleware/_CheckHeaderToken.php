<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Helpers\HeaderTokenValidator;

class _CheckHeaderToken 
{
    public function handle($request, Closure $next){
        
        // проверка токена
        $result = HeaderTokenValidator::validate_token($request);
        if ( is_int($result) == false){ return $result; }

        // добавляем в реквест наш юзер ид, полученный из токена
        $request['user_id'] = $result;

        return $next($request);
    }
}
