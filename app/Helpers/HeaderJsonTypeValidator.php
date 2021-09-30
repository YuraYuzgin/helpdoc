<?php

namespace App\Helpers;

class HeaderJsonTypeValidator {

    /**
     * @param $request
     * @return bool|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    // проверяем тип заголовка, если не application/json, то возвращаем ошибку
    public static function validate_json_header($request){
        if (strtolower($request->header('Content-Type')) !== 'application/json'){
            $content = array('status'=>'Content-Type not json');
            $content = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return response($content, 400);
        }
        else{
            $result = true;
            return $result;
        }
    }
}
