<?php

namespace App\Http\Controllers\Search;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Helpers\ResponseHelper;
use DB;
use Carbon\Carbon;

class SearchController extends Controller{

    /*
    Пример заполнения запроса
    Передавать только заполненные поля

    body:
    {
    "user":{
        "first_name":"Иван",
        "middle_name":"Иванович",
        "last_name":"Иванов",
        "email":"ivanov@mail.com",
        "phone_number":"79185557710",
        "city_id":15
    },
    "age":{
        "age_start":20,
        "age_end":40
    },
    "job":[
	    {"job_oid":"1.2.643.5.1.13.13.12.2.77.7799"},
	    {"job_oid":"1.2.643.5.1.13.13.12.2.77.7798"}
    ],
    "spec":[
	    {"spec_id":9},
	    {"spec_id":10}
    ],
    "interests":[3, 4, 7]
    }

    Или:

    body:
    {
    "user":{
        "last_name":"Иванов"
    }
 
    */

    /*
    Общий смысл строки запроса
    "(first_name = ? AND middle_name = ? AND last_name = ? AND email = ? AND phone_number = ? AND city_id = ?) 
    AND (age <= ? AND age>= ?) AND (job_oid = ? OR job_oid = ?) AND (spec_id = ? OR spec_id = ?) 
    AND (interest_id = ? OR interest_id = ? OR interest_id = ?)"
    */

    
    //отдаёт пользователей по заданным параметрам на основе принятого джейсона
    //доступ только по токену пользователя
    public function search_users(Request $request){
        // Если не было заполнено ни одно поле
        if ((!isset($request) || ($request === NULL)) || 
            ((!isset($request->user) || ($request->user === NULL)) && 
            (!isset($request->age) || ($request->age === NULL)) &&
            (!isset($request->job) || ($request->job === NULL)) &&
            (!isset($request->spec) || ($request->spec === NULL)) &&
            (!isset($request->interests) || ($request->interests === NULL)))){

            // Делаем запрос в БД. Собираем всю информацию, кроме специализаций и мед организаций
            try{
                $result = DB::table('users')
                    ->leftJoin('users_job_places as ujp', 'ujp.user_id', '=','users.id')
                    ->leftJoin('med_orgs as mo', 'oid', '=','ujp.job_oid')
                    ->leftJoin('users_specializations as us', 'us.user_id', '=','users.id')
                    ->leftJoin('specializations as s', 's.id', '=','us.spec_id')
                    ->leftJoin('users_sc_interests as usi', 'usi.user_id', '=','users.id')
                    ->select('users.id', 'users.first_name', 'users.middle_name', 'users.last_name', 'foto')
                    ->distinct()
                    ->get();
            }
            catch (Exception $e) {
                return ResponseHelper::response_error_read_db();
            }
        }
        // Если есть заполненные поля
        else{
            // Массив для добавления частей запроса where
            $array_fields = array();
            // Массив для добавления значений заполненных полей
            $array_values = array();

            // Создаём часть строки из данных "user", если есть заполенные поля
            // Если заполнены все поля:
            // "first_name = ? AND middle_name = ? AND last_name = ? AND email = ? AND phone_number = ? AND city_id = ?"
            if (isset($request->user) && ($request->user !== NULL) ){
                $fields_user = "";
                foreach ($request->user as $key => $value) {
                    $fields_user .= $key . " = ? AND ";
                    $array_values[] = $value;
                }
                $fields_user = "(" . substr($fields_user, 0, -5) . ")";
                
                // Присоединяем строку к масиву
                $array_fields[] = $fields_user;
            }

            // Создаём часть строки из данных "age", если есть заполенные поля
            // Если заполнены все поля:
            // "age <= ? AND age >= ?"
            if (isset($request->age) && ($request->age !== NULL) ){

                // Получаем даты для запроса на основе переданных ограничений по возрасту
                $age_start = $request->age['age_start'];
                $date_start = Carbon::now()->subYears($age_start)->format('Y-m-d');

                $age_end = $request->age['age_end'];
                $date_end = Carbon::now()->subYears($age_end)->format('Y-m-d');

                $fields_age = "";
                foreach ($request->age as $key => $value) {
                    //если заполнен "age_start"
                    if($key === "age_start") {
                        $fields_age .= "birthday <= ? AND ";
                        $array_values[] = $date_start;
                    }
                    //если заполнен "age_start"
                    if($key === "age_end") {
                        $fields_age .= "birthday >= ? AND ";
                        $array_values[] = $date_end;
                    }
                }
                $fields_age = "(" . substr($fields_age, 0, -5) . ")";

                // Присоединяем строку к масиву
                $array_fields[] = $fields_age;
            }

            // Создаём часть строки из данных "job", если есть заполенные поля
            // Если заполнены все поля:
            // "job_oid = ? OR job_oid = ?"
            if (isset($request->job) && ($request->job !== NULL) ){
                $fields_job = "";
                foreach ($request->job as $key => $value) {
                    foreach ($value as $k => $v) {
                        $fields_job .= $k . " = ? OR ";
                        $array_values[] = $v;
                    }
                }
                $fields_job = "(" . substr($fields_job, 0, -4) . ")";

                // Присоединяем строку к масиву
                $array_fields[] = $fields_job;
            }

            // Создаём часть строки из данных "spec", если есть заполенные поля
            // Если заполнены все поля:
            // "spec_id = ? OR spec_id = ?"
            if (isset($request->spec) && ($request->spec !== NULL) ){
                $fields_spec = "";
                foreach ($request->spec as $key => $value) {
                    foreach ($value as $k => $v) {
                        $fields_spec .= $k . " = ? OR ";
                        $array_values[] = $v;
                    }
                }
                $fields_spec = "(" . substr($fields_spec, 0, -4) . ")";

                // Присоединяем строку к масиву
                $array_fields[] = $fields_spec;
            }
            
            // Создаём часть строки из данных "interests", если есть заполенные поля
            // Если заполнены все поля:
            // "interest_id = ? OR interest_id = ? OR interest_id = ?"
            if (isset($request->interests) && ($request->interests !== NULL) ){
                $fields_interests = "";
                foreach ($request->interests as $key) {
                    $fields_interests .= "interest_id = ? OR ";
                    $array_values[] = $key;
                }
                $fields_interests = "(" . substr($fields_interests, 0, -4) . ")";

                // Присоединяем строку к масиву
                $array_fields[] = $fields_interests;
            }

            // Создаём итоговую строку запроса where
            
            // Создаём итоговую строку из массива
            $query = implode(" AND ", $array_fields);

            // Делаем запрос в БД. Собираем всю информацию, кроме специализаций и мед организаций
            try{
                $result = DB::table('users')
                    ->leftJoin('users_job_places as ujp', 'ujp.user_id', '=','users.id')
                    ->leftJoin('med_orgs as mo', 'oid', '=','ujp.job_oid')
                    ->leftJoin('users_specializations as us', 'us.user_id', '=','users.id')
                    ->leftJoin('specializations as s', 's.id', '=','us.spec_id')
                    ->leftJoin('users_sc_interests as usi', 'usi.user_id', '=','users.id')
                    ->select('users.id', 'users.first_name', 'users.middle_name', 'users.last_name', 'foto')
                    ->whereRaw("$query", $array_values)
                    ->distinct()
                    ->get();
            }
            catch (Exception $e) {
                return ResponseHelper::response_error_read_db();
            }
        }

        // Массив id пользователей
        $array_id = array();
        foreach ($result as $key => $value) {
            foreach ($value as $k => $v) {
                // Проверяем, чтобы id не повторялся
                if(($k == 'id') && (!in_array($v, $array_id))) $array_id[] = $v;
            }
        }

        // Делаем запрос в БД. Ищем главные специализации по id пользователя
        try{
            $result_spec_main = DB::table('users')
                ->leftJoin('users_specializations as us', 'us.user_id', '=','users.id')
                ->leftJoin('specializations as s', 's.id', '=','us.spec_id')
                ->select('users.id', 's.name as spec_main')
                ->whereIn('users.id', $array_id)
                ->where('us.is_main', 1)
                ->distinct()
                ->get();
        }
        catch (Exception $e) {
            return ResponseHelper::response_error_read_db();
        }

        // Делаем запрос в БД. Ищем второстепенные специализации по id пользователя
        try{
            $result_spec_not_main = DB::table('users')
                ->leftJoin('users_specializations as us', 'us.user_id', '=','users.id')
                ->leftJoin('specializations as s', 's.id', '=','us.spec_id')
                ->select('users.id', 's.name as spec_not_main')
                ->whereIn('users.id', $array_id)
                ->where('us.is_main', 0)
                ->distinct()
                ->get();
        }
        catch (Exception $e) {
            return ResponseHelper::response_error_read_db();
        }
        
        // Делаем запрос в БД. Ищем главные места работы по id пользователя
        try{
            $result_job_main = DB::table('users')
                ->leftJoin('users_job_places as ujp', 'ujp.user_id', '=','users.id')
                ->leftJoin('med_orgs as mo', 'oid', '=','ujp.job_oid')
                ->select('users.id', 'mo.nameShort as name_job_main')
                ->whereIn('users.id', $array_id)
                ->where('ujp.is_main', 1)
                ->distinct()
                ->get();
        }
        catch (Exception $e) {
            return ResponseHelper::response_error_read_db();
        }

        // Делаем запрос в БД. Ищем второстепенные места работы по id пользователя
        try{
            $result_job_not_main = DB::table('users')
                ->leftJoin('users_job_places as ujp', 'ujp.user_id', '=','users.id')
                ->leftJoin('med_orgs as mo', 'oid', '=','ujp.job_oid')
                ->select('users.id', 'mo.nameShort as name_job_not_main')
                ->whereIn('users.id', $array_id)
                ->where('ujp.is_main', 0)
                ->distinct()
                ->get();
        }
        catch (Exception $e) {
            return ResponseHelper::response_error_read_db();
        }

        // Переводим результаты запросов в БД в ассоциативные массивы
        $array_result = json_decode($result, true);
        $array_result_spec_main = json_decode($result_spec_main, true);
        $array_result_spec_not_main = json_decode($result_spec_not_main, true);
        $array_result_job_main = json_decode($result_job_main, true);
        $array_result_job_not_main = json_decode($result_job_not_main, true);

        // Добавление к результату специализаций
        // В итоге: главная специализация и второстепенная
        foreach ($array_result as $key => $value) {
            $array_result[$key]['spec_main'] = null;
            $array_result[$key]['spec_not_main'] = null;
            $array_result[$key]['name_job_main'] = null;
            $array_result[$key]['name_job_not_main'] = null;

            foreach ($value as $k => $v) {
                // Добавляем данные по главным специализациям
                foreach ($array_result_spec_main as $key2 => $value2) {
                    foreach ($value2 as $k2 => $v2) {
                        if($v === $v2){
                            $t2 = $value2['spec_main'];
                            $array_result[$key]['spec_main'] = $t2;
                        }
                    }
                }
                // Добавляем данные по второстепенным специализациям
                foreach ($array_result_spec_not_main as $key3 => $value3) {
                    foreach ($value3 as $k3 => $v3) {
                        if($v === $v3){
                            $t3 = $value3['spec_not_main'];
                            $array_result[$key]['spec_not_main'] = $t3;
                        }
                    }
                }
                // Добавляем данные по главным местам работы
                foreach ($array_result_job_main as $key4 => $value4) {
                    foreach ($value4 as $k4 => $v4) {
                        if($v === $v4){
                            $t4 = $value4['name_job_main'];
                            $array_result[$key]['name_job_main'] = $t4;
                        }
                    }
                }
                // Добавляем данные по второстепенным местам работы
                foreach ($array_result_job_not_main as $key5 => $value5) {
                    foreach ($value5 as $k5 => $v5) {
                        if($v === $v5){
                            $t5 = $value5['name_job_not_main'];
                            $array_result[$key]['name_job_not_main'] = $t5;
                        }
                    }
                }
            }
        }
        
        // Отсеиваем пользователей, у которых не заполнено ни одно поле name
        foreach ($array_result as $key => $value) {
            if(($value["first_name"] === null) &&
               ($value["middle_name"] === null) &&
               ($value["last_name"] === null)){
                unset($array_result[$key]);
               }
        }

        return ResponseHelper::response_ok($array_result);
    }
}