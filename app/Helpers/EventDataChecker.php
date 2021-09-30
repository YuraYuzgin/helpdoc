<?php

namespace App\Helpers;

use App\Helpers\ResponseHelper;

class EventDataChecker {

    /**
     * @param $request
     * @return bool|\Illuminate\Contracts\Routing\ResponseFactory|\Illuminate\Http\Response
     */
    // проверяем тип заголовка, если не application/json, то возвращаем ошибку
    public static function validate_json_data_for_event($request){
        
        // наличие поля start_date обязательно 
        if ( !isset($request->event['start_date']) ){
            return ResponseHelper::custom_response("field start_date is required and STR 'y-m-d h:m:s'", 403);
        }

        // наличие поля end_date обязательно 
        if ( !isset($request->event['end_date']) ){
            return ResponseHelper::custom_response("field end_date is required and STR 'y-m-d h:m:s'", 403);
        }

        $start_date = strtotime($request->event['start_date']);
        $end_date = strtotime($request->event['end_date']);
        // конец события не может быть раньше чем его начало
        if ( $end_date < $start_date){
            return ResponseHelper::custom_response("end_date must be later than start_date", 403);
        }

        // наличие поля title обязательно 
        if ( !isset($request->event['title']) ){
            return ResponseHelper::custom_response("field title is required and STR", 403);
        }
        
        // наличие поля event_type обязательно 
        if ( !isset($request->event['event_type']) ){
            return ResponseHelper::custom_response("field event_type is required and STR", 403);
        }

        // event_type может быть только значением 'reception','administrative','scientific','another'  
        $valid_event_types = array('reception','administrative','scientific','another');
        if (!in_array($request->event['event_type'], $valid_event_types)) {
            return ResponseHelper::custom_response("field  event_type is incorrect. Valid values: " . implode(', ', $valid_event_types), 403);
        }

        // если все проверки прошли:
        return true;
    }
}
