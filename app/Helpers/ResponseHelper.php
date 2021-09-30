<?php

namespace App\Helpers;

class ResponseHelper {

    /**
     * @param $text
     * @param $status
     * @return \Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    // на вход текс для jsona и статус ответа, формирует ответ и отдает его классом Response
    public static function custom_response($text, $status){
        $content = array("status"=>"{$text}");
        $content = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return response($content, $status);
    }

     // формирует ответ об ошибке чтения базы данных (статус 500) и отдаёт его классом Response
     public static function response_error_read_db($error_text=""){
        $text = 'Ошибка чтения базы данных. Повторите попытку позже.'.$error_text;
        // return ResponseHelper::custom_response($text, 500);
        return self::custom_response($text, 500);
    }

     // формирует ответ об ошибке записи в базу данных (статус 500) и отдаёт его классом Response
     public static function response_error_write_db($error_text=""){
        $text = 'Ошибка записи в базу данных. Повторите попытку позже.'.$error_text;
        // return ResponseHelper::custom_response($text, 500);
        return self::custom_response($text, 500);
    }

     // формирует ответ об ошибке удаления информации из базы данных (статус 500) и отдаёт его классом Response
     public static function response_error_delete_from_db(){
        $text = 'Ошибка удаления из базы данных. Повторите попытку позже.';
        // return ResponseHelper::custom_response($text, 500);
        return self::custom_response($text, 500);
    }

    // формирует конечный ответ на запрос пользователя (статус 200) и отдаёт его классом Response
    public static function response_ok($param='OK'){
        $content = json_encode($param, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        return response($content, 200);
    }
}
