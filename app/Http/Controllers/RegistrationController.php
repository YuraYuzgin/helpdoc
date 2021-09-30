<?php

namespace App\Http\Controllers;
// namespace App\Models;


use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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


//use App\Models\Users;
use DB;
use Mail;

class RegistrationController extends Controller
{
    /*
        -------------------------------
                create_account
        -------------------------------
    */
    public function create_account(Request $request)
    {
        // проверяем наличие в json-е поля email
        if (!$request->has('email')){
            $text = 'json not valid. \'email\' field required and STR type';
            return ResponseHelper::custom_response($text, 400);
        }

        // проверяем заполнено ли поле email через встроенный валидатор laravel
        try{
            $this->validate($request, ['email' => ['required']]);
        }
        catch (\Exception $e){
            $text = 'json not valid. \'email\' field cannot be empty';
            return ResponseHelper::custom_response($text, 400);
        }

        // проверяем email на корректность (проверяет по маске, не допускает рус языка в адресе)
        if(!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
            $text = 'json not valid. \'email\' format is not correct';
            return ResponseHelper::custom_response($text, 400);
        }
        
        // проверяем не зарегистрирован ли данный email ранее через встроенный валидатор laravel
        try{
            $this->validate($request, ['email' => ['unique:users']]);
        }
        catch (\Exception $e){
            $text = 'user with this email is already registered';
            return ResponseHelper::custom_response($text, 403);
        }
      
        // генерируем пароль юзера
        $password = NewPasswordGenerator::random_password(10);
        // хешируем для дальнейшего хранения
        $hashed_password = Hash::make($password);

        $user_email = $request->email;
        $data = array(
            'user_email' => $user_email,
            'password' => $password,
        );

        // записываем пароль и емейл в базу и получаем id пользователя
        try{
            $user_id = DB::table('users')->insertGetId(
                ['email' => $user_email, 'password' => $hashed_password]
            );
        }
        catch (\Exception $e){
            $text = 'error creating user';
            return ResponseHelper::custom_response($text, 500);
        }

        // формируем и отправляем письмо пользователю на указанное мыло с паролем
        try{
            Mail::send('emails.send_password', $data, function($message) use ($user_email)
            {
                $message->to($user_email, '')->subject('Ваши доступы к сервису HelpDoctor!');
                $message->from('helpdoctor.supp@gmail.com', 'HelpDoctor');
            });
        }
        catch (\Exception $e){
            $text = 'user is created, but there was error an sending email';
            return ResponseHelper::custom_response($text, 500);
        }

        // создаём базовые группы контактов пользователя
        try{
            DB::table('contact_groups')
                ->insert(['group_name' => null, 'group_owner_id' => $user_id]);
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_write_db();
        }

        // отвечаем что все операции выше прошли успешно
        return ResponseHelper::custom_response('OK', 200);
    }


    /*
        -------------------------------
                delete_account_by_emeil
        -------------------------------
    */
    //удаление пользовательского аккаунта для тестов
    public function delete_account_by_emeil(Request $request, $email){
        // посылаем запрос в базу и смотрим нет ли унас уже такого е-мейла
        // $user_email = $request->email;
        $user = DB::table('users')
            ->whereIn('email', [$email])
            ->get();
        // $user - объект коллекции https://laravel.com/docs/5.8/collections

        // если есть, то отвечаем текстом ниже
        if ($user->isNotEmpty()) {
            // перепресваиваем переменной вместо коллекции ее первый и единственный элемент
            $user = $user->first();
            // удаляемиз всех таблиц данные, связанные с пользователем, если они есть
            DB::table('users')->where('email', '=', $email)->delete();
            DB::table('users_auth')->where('user_id', '=', $user->id)->delete();
            DB::table('users_job_places')->where('user_id', '=', $user->id)->delete();
            DB::table('users_sc_interests')->where('user_id', '=', $user->id)->delete();
            DB::table('users_specializations')->where('user_id', '=', $user->id)->delete();
            DB::table('user_events')->where('user_id', '=', $user->id)->delete();
            $text = 'Пользователь с таким почтовым адресом только что был удален';
            return ResponseHelper::custom_response($text, 200);
        }
        else{
            $text = 'Пользователь с таким почтовым адресом не зарегистрирован';
            return ResponseHelper::custom_response($text, 403);
        }
    }

    /*
        -------------------------------
                delete_account
        -------------------------------
    */
    //удаление пользовательского аккаунта кнопкой из приложения
    public function delete_account(Request $request){

        // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
        $user_id = $request->user_id;

        // получаем contact_id для удаления таблиц
        try{
            $contacts_id = DB::table('contact_groups as cg')
                ->leftJoin('contacts_and_groups_connection as cagc', 'cagc.contact_group_id', '=', 'cg.id')
                ->select('contact_id')
                ->where('group_owner_id', $user_id)
                ->get();
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_delete_from_db();
        }

        // переводим результат в массив
        $array_contacts_id = array();
        foreach ($contacts_id as $key => $value) {
            foreach ($value as $k => $v) {
                $array_contacts_id[] = $v;
            }
        }

        // удаляем из всех таблиц данные, связанные с пользователем, если они есть
        try{
            DB::transaction(function() use ($user_id, $array_contacts_id){
                DB::table('users')->where('id', '=', $user_id)->delete();
                DB::table('users_auth')->where('user_id', '=', $user_id)->delete();
                DB::table('users_job_places')->where('user_id', '=', $user_id)->delete();
                DB::table('users_sc_interests')->where('user_id', '=', $user_id)->delete();
                DB::table('users_specializations')->where('user_id', '=', $user_id)->delete();
                DB::table('user_events')->where('user_id', '=', $user_id)->delete();
                DB::table('contact_groups')->where('group_owner_id', '=', $user_id)->delete();
                DB::table('contacts_and_groups_connection')->whereIn('contact_id', $array_contacts_id)->delete();
                DB::table('contacts')->whereIn('id', $array_contacts_id)->delete();
            }, 3);  // пытаемся провести 3 раза транзакцию, при возникновении ошибок, можно менять число на сколько надо
        }
        catch (\Exception $e){  
            // если транзакция не удачна, то  говорим юзеру что мы не можен в данный момент
            // и повтори еще раз позднее
            $text = 'operation failed, try again later -- '.$e;
            return ResponseHelper::custom_response($text, 500); 
        }
        // если все прошло успешно
        return ResponseHelper::response_ok();
    }

    /*
        -------------------------------
            recovery_account_password
        -------------------------------
    */
    public function recovery_account_password(Request $request){

        // проверяем наличие в json-е поля email
        if (!$request->has('email')){
            $text = 'json not valid. \'email\' field required and STR type';
            return ResponseHelper::custom_response($text, 400);
        }
        // посылаем запрос в базу и смотрим есть ли унас уже юзер с таким е-мейлом
        $user_email = $request->email;
        $user = DB::table('users')
            ->whereIn('email', [$user_email])
            ->get();
        // $user - объект коллекции https://laravel.com/docs/5.8/collections
        // если есть, то отвечаем текстом ниже
        if ($user->isEmpty()) {
            $text = 'Пользователь с таким почтовым адресом не зарегистрирован';
            return ResponseHelper::custom_response($text, 403);
        }
        // генерируем пароль юзера
        $password = NewPasswordGenerator::random_password(10);
        // хешируем для дальнейшего хранения
        $hashed_password = Hash::make($password);

        $data = array(
            'user_email' => $user_email,
            'password' => $password,
        );
        // формируем и отправляем письмо пользователю на указанное мыло с паролем
        
        try{
            Mail::send('emails.recovery_password', $data, function($message) use ($user_email)
            {
                $message->to($user_email, '')->subject('Ваш новый пароль к сервису HelpDoctor!');
                $message->from('helpdoctor.supp@gmail.com', 'HelpDoctor');
            });
        }
        catch (\Exception $e){
            $text = 'Ошибка при отправке письма сервисом или указан несуществующий e-mail -- '.$e;
            return ResponseHelper::custom_response($text, 500);
        }
        
            // записываем пароль и емейл в базу
        try{
            // перепресваиваем переменной вместо коллекции ее первый и единственный элемент
            $user = $user->first();
            // апдейтим запись в БД по ид
            DB::table('users')
            ->where('id', $user->id)
            ->update([
                'password' => $hashed_password,
                'date_last_login' => null
            ]);
        }
        catch (\Exception $e){
            $text = 'Ошибка при записи в базу данных, повторите процесс восстановления пароля';
            return ResponseHelper::custom_response($text, 500);
        }
        // отвечаем что все операции выше прошли успешно
        return ResponseHelper::custom_response('OK', 200);
    }
}
