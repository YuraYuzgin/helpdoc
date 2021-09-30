<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Middleware;

// Helpers:
// use App\Helpers\HeaderJsonTypeValidator;
use App\Helpers\ResponseHelper;

// подключение БД
use DB;

class EventController extends Controller{

    /*
    *
    * создает запись в календаре на основе принятого джейсона
    * доступ только по токену пользователя
    *
    */
    // public function __construct(){
    //     // $this->middleware('ValidateHeaderJsonType');
    //     $this->middleware('check.header');
    // }

    public function set_event(Request $request){
        // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
        // $user_id = $request->user_id;

        /*
        *
        * написать и добавить хелпер или ище мидлвару на проверку валидности входных данных
        *
        */

        /*
        {
        "event":{
            "id":2,   // для апдейт указывает ид, если создание  записи то этого ключа нет
            "start_date":"2019-11-07 21:00:00",
            "end_date":"2019-11-07 21:30:00",
            "notify_date":"2019-11-07 20:45:00",
            "title":"Тестовый прием",
            "description":"тостовый прием тестового пациента",
            "is_major":true,
            "event_place":"РнД, больница №666",
            "reception_patients":true
            }
        }
        */

         
         // если id есть, то обновляем существующую
         if ( isset($request->event) && isset($request->event['id']) ){
             // обновляем данные в базе  из массива
             try{
                DB::table('user_events')
                ->where(['id'=>$request->event['id'], 'user_id'=>$request->user_id])
                    ->update($request->event);
            }
            catch (\Exception $e){
                return ResponseHelper::response_error_write_db();
            }
         }
         //если нет id то создаем запись
         else if ( isset($request->event) && ! isset($request->event['id']) ) {
            $event=$request->event;
            $event['user_id'] = $request->user_id;
            // echo $event['user_id'];
            try{
                DB::table('user_events')
                    ->insert($event);
            }
            catch (\Exception $e){
                return ResponseHelper::response_error_write_db();
            }
         }

        return ResponseHelper::custom_response('OK', 200);
    }


    /*
    *
    * отдает  события за указанную дату
    * доступ только по токену пользователя
    *
    */

    /*
        http://hd/public/api/event/date/2019-11-07  //пример вызова, не забываем токен в хеде
    */
    public function get_event_on_date(Request $request, $year, $month, $day){
        $date = "{$year}-{$month}-{$day}";
        
        try{

            $events = DB::select("SELECT * FROM `user_events` WHERE DATE(`start_date`) = '$date' AND `user_id`=$request->user_id");
                
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db($e);
        }

        return ResponseHelper::response_ok($events);
    }

}
