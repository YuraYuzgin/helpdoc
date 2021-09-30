<?php

namespace App\Http\Controllers\CustomAuth;
// namespace App\Models;


use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
/*
 * The Laravel Hash facade provides secure Bcrypt hashing for storing user passwords.
 * Basic usage required two things:
 * First include the Facade in your file
 *  * use Illuminate\Support\Facades\Hash;
 * and use Make Method to generate password.
 *  * $hashedPassword = Hash::make($request->newPassword);
 * and when you want to match the Hashed string you can use the below code:
 *  * Hash::check($request->newPasswordAtLogin, $hashedPassword)
 * You can learn more with the Laravel document link below for Hashing: https://laravel.com/docs/5.5/hashing
 */


// Helpers:
use App\Helpers\HeaderJsonTypeValidator;
use App\Helpers\ResponseHelper;
use App\Helpers\NewPasswordGenerator;
use App\Helpers\HeaderTokenValidator;


//use App\Models\Users;
use DB;
use Mail;

class AuthController extends Controller
{
     /*
        -------------------------------
                login_user
        -------------------------------
    */
    public function login_user(Request $request){
        
        // проверяем наличие в json-е поля email
        if (!$request->has('email')){
            $text = 'json not valid. \'email\' field required and STR type';
            return ResponseHelper::custom_response($text, 400);
        }

        // проверяем что поле емейл не пустое
        if ($request->email == ""){
            $text = 'email field cannot be empty.';
            return ResponseHelper::custom_response($text, 403);
        }

        // проверяем дополнительно формат емейла на валидность
        if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $text = 'email format is incorrect';
            return ResponseHelper::custom_response($text, 403);
        } 
        
        // проверяем наличие в json-е поля password
        if (!$request->has('password')){
            $text = 'json not valid. \'password\' field required and STR type';
            return ResponseHelper::custom_response($text, 400);
        }


        // посылаем запрос в базу и смотрим есть ли унас уже юзер с таким е-мейлом
        try{
            $user_email = $request->email;
            $user = DB::table('users')
                ->whereIn('email', [$user_email])
                ->get();
        // $user - объект коллекции https://laravel.com/docs/5.8/collections
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }
        
        // если есть, то отвечаем текстом ниже
        if ($user->isEmpty()) {
            $text = 'Пользователь с таким почтовым адресом не зарегистрирован';
            return ResponseHelper::custom_response($text, 403);
        }
        else{
            // перепресваиваем переменной вместо коллекции ее первый и единственный элемент
            $user = $user->first();
        }

        // раскодируем пароль из base64 и сверяем с раскодированным из базы
        $user_password = $request->password;
        if ( ! Hash::check(base64_decode($user_password), $user->password) ){
            $text = 'Неверный пароль.';
            return ResponseHelper::custom_response($text, 401);
        }

        // получаем сегодняшнюю дату
        $today = date("Y-m-d");

        // апдейтим запись юзера в БД по ид 
        try{
            DB::table('users')
            ->where('id', $user->id)
            ->update([
                'date_last_login' => $today,
                'validate' => 1,
            ]);
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_write_db();
        }

        // проверяем наличие устаревших токенов и удаляем их
        try{
            DB::table('users_auth')->where('date_expire', '<', $today)->delete();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_write_db();
        }

        // создаем новый токен сроком жизни 180 дней и записываем его в базу
        // $token = NewPasswordGenerator::random_password(32);
        $token = Str::random(64);
        try{
            $id = DB::table('users_auth')->insertGetId(
                array(
                    'user_id' => $user->id,
                    'token' => $token,
                    'date_expire' => date('Y-m-d', strtotime('+180 days'))
                )
            );
        }  
        catch (\Exception $e){
            return ResponseHelper::response_error_write_db();
        }


        // отвечаем что все операции выше прошли успешно
        $content = array(
            'status'=>'OK',
            'X-Auth-Token'=>"{$id}_{$token}"
        );

        return ResponseHelper::response_ok($content);
    }

    /*
        -------------------------------
                logout_user
        -------------------------------
    */
    // функция разлогинивания
    public function logout_user(Request $request){
        
        // удаление строчки с токеном из БД
        try{
            DB::table('users_auth')->where('id', '=', $request->user_id)->delete();
        }
        catch (\Exception $e){
            $text = 'Ошибка при разлогинивании. Повторите попытку позднее.';
            return ResponseHelper::custom_response($text, 500);
        }

        // если удаление прошло успешно
        return ResponseHelper::response_ok();
    }

}