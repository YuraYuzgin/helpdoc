<?php

namespace App\Http\Controllers\Events;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
//use App\Http\Middleware;

// Helpers:
// use App\Helpers\HeaderJsonTypeValidator;
use App\Helpers\ResponseHelper;
use App\Helpers\EventDataChecker;

// подключение БД
use DB;

// отвечает за удаление, обновление, добавление и получение пользовательский событий
class UserEventController extends Controller{
    /*
        -------------------------------
                set_event
        -------------------------------
    */
    //создает запись в календаре на основе принятого джейсона
    //доступ только по токену пользователя
    public function set_event(Request $request){
        
       // валидируем входные данные json-a с описанием  события
       $result = EventDataChecker::validate_json_data_for_event($request);
       if ($result !== true){ return $result; }

         
         // если id есть, то обновляем существующую
        if ( isset($request->event) && isset($request->event['id']) ){
             // обновляем данные в базе  из массива
            //try{
                $result = DB::table('user_events')
                    ->where(['id'=>$request->event['id'], 'user_id'=>$request->user_id])
                    ->first();
                if ($result == null){
                    return ResponseHelper::custom_response("event with this id does not exist for the user", 404);
                }
                
                DB::table('user_events')
                ->where(['id'=>$request->event['id'], 'user_id'=>$request->user_id])
                    ->update($request->event);
            //}
            //catch (\Exception $e){
            //    return ResponseHelper::response_error_write_db();
            //}
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
        -------------------------------
                get_event_on_date
        -------------------------------
    */
    //http://hd/public/api/event/date/2019-11-07  //пример вызова, не забываем токен в хеде
    //отдает  события за указанную дату
    //доступ только по токену пользователя
    public function get_event_on_date(Request $request, $year, $month, $day){
        $date = "{$year}-{$month}-{$day}";
        
        try{

            $events = DB::select("SELECT id, start_date, end_date, notify_date, title, description, is_major,
                event_place, event_type FROM `user_events` WHERE DATE(`start_date`) = '$date' AND `user_id`=$request->user_id");
            
            // преобразуем поле is_major из 1/0 в true/false если была получена хотябы одна запись из БД
            if (count($events) !== 0){
                foreach ($events as $event){
                    $event->is_major =  $event->is_major ? true : false;
                }
            }    
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }

        return ResponseHelper::response_ok($events);
    }


    /*
        -------------------------------
                delete_event
        -------------------------------
    */
    // http://hd/public/api/event/del/15  //пример вызова, не забываем токен в хеде
    //удаляет запись в календаре на основе переданного get-параметра (id события)
    //доступ только по токену пользователя
    public function delete_event(Request $request, $event_id){
        try{
            $result = DB::table('user_events')
                ->where('id', $event_id)
                ->where('user_id', $request->user_id)
                ->delete();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }

        // если по переданному id ничего не найдено или передали ид записи другого пользователя
        if ($result == 0) {
            $text = "event not found";
            return ResponseHelper::custom_response($text, 404);
        }

        return ResponseHelper::custom_response('OK', 200);
    }

   
    /*
        -------------------------------
                get_event
        -------------------------------
    */
    // http://hd/public/api/event/get/15  //пример вызова, не забываем токен в хеде
    // отдает конкретное событие на основе переданного get-параметра (id события)
    // доступ только по токену пользователя 
    public function get_event(Request $request, $event_id){
        try{
            $event = DB::table('user_events')
                ->select( 'id', 'start_date', 'end_date', 'notify_date', 'title',
                    'description', 'is_major', 'event_place', 'event_type') 
                ->where('id', $event_id)
                ->where('user_id', $request->user_id)
                ->first();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }

        // если по переданному id ничего не найдено
        if ($event === NULL) {
            $text = "event not found";
            return ResponseHelper::custom_response($text, 404);
        }

        // преобразуем поле is_major из 1/0 в true/false
        $event->is_major =  $event->is_major ? true : false;
        
        return ResponseHelper::response_ok($event);
    }
}
