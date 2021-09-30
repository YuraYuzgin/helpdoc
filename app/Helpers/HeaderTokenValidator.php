<?php

namespace App\Helpers;

use DB;
use Illuminate\Support\Str;

class HeaderTokenValidator {
    // если валидация прошла то функция вернет id пользователя, 
    // если нет - то какую то из возможных ошибок
    public static function validate_token($request){
        // сверяем наличие заголовка
        if ($request->header('X-Auth-Token')){
            // ничего не делаем
        }
        else{
            $content = array('status'=>'X-Auth-Token required');
            $content = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return response($content, 400);
        }

        // заголовок не должен быть пустым
        if ( ($request->header('X-Auth-Token') == '') || ($request->header('X-Auth-Token') == null) ){
            $content = array('status'=>'X-Auth-Token cannot be empty');
            $content = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return response($content, 400);
        }

        $token = $request->header('X-Auth-Token');
        // токен должен  совпадать с регуляркой 
        if (! preg_match('/^\d+_[^_].*$/', $token)){
            $content = array('status'=>'X-Auth-Token is incorrect');
            $content = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return response($content, 400);
        }

        list($id, $token)=explode("_", $token, 2);
        // кидаем запрос в базу и ищем строчку по ид
        try{

            $result = DB::table('users_auth')
                ->where('id', $id)->first();
        }
        catch (\Exception $e){
            $text = 'Ошибка чтения базы данных. Повторите попытку позднее.';
            return ResponseHelper::custom_response($text, 500);
        }
        
        // если нет, то отвечаем текстом ниже
        if ( $result == null ) {
            $text = 'Token id not found';
            return ResponseHelper::custom_response($text, 400);
        }

        if ( $token !== $result->token){
            $content = array('status'=>'Token is incorrect');
            $content = json_encode($content, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            return response($content, 400);
        }

        // юзер валидирован,  апдейтим дату истечения  токена
        try{
            DB::table('users_auth')
            ->where('id', $id)
            ->update(array('date_expire' => date('Y-m-d', strtotime('+180 days'))));
        }  
        catch (\Exception $e){
            $text = 'Ошибка записи в базу данных. Повторите попытку позднее.';
            return ResponseHelper::custom_response($text, 500);
        }

        // возвращаем id юзера
        $result = $result->user_id;
        // $result = true;
        return $result;



    }
}