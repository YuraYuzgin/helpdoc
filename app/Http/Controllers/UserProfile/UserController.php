<?php

namespace App\Http\Controllers\UserProfile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

use App\Helpers\HeaderJsonTypeValidator;
use App\Helpers\HeaderTokenValidator;
use App\Helpers\ResponseHelper;


class UserController extends Controller{
    /*
        -------------------------------
                profile_checker
        -------------------------------
    */
    # Проверяет заполненность профиля пользователя по основным полям
    public function profile_checker(Request $request){
        
        // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
        $user_id = $request->user_id;

        // кидаем запрос в базу и собираем массив из обязательных к заполнению полей
        try{ 
            $user_profile_rows = DB::table('users')
                ->leftJoin('users_job_places', 'users.id', '=', 'users_job_places.user_id', 'AND', 'users_job_places', '=', '1')
                ->leftJoin('users_specializations', 'users.id', '=', 'users_specializations.user_id')
                ->select('users.first_name', 'users.last_name', 'users.phone_number', 'users.birthday', 'users.city_id',
                        'users_job_places.job_oid', 'users_specializations.spec_id')
                ->where('users.id', '=', $user_id)
                ->first();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }

        // просматриваем полученный массив на NULL-ы, если есть, то значит 
        // профиль недозаполнен,  возвращаем false
        foreach ($user_profile_rows as $row) {
            if ($row == NULL){
                return ResponseHelper::custom_response('False', 200);
                //break;
            }
          }
        
        // если  профиль оказался заполненным, то возвращаем tru
        return ResponseHelper::custom_response('True', 200);
          
        // сырой запрос в БД
        /*
            SELECT
            u.first_name, u.last_name, u.phone_number, u.city_id,
            ujp.job_id,
            usi.interest_id
            FROM `users` u
            LEFT JOIN `users_job_places` ujp ON ((u.id = ujp.user_id) AND (ujp.is_main = 1))
            LEFT JOIN `users_sc_interests` usi ON (u.id = usi.user_id)
            WHERE (u.id = 123)     // 123 - ID пользователя
        */

    } // -- end function profile_checker


     /*
        -------------------------------
                profile_updater
        -------------------------------
    */
    # добавляет или обновляет информацию в профиле пользователя
    public function profile_updater(Request $request){
                
        // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
        $user_id = $request->user_id;

        //если в user есть какая то информация
        if (isset($request->user) && ($request->user !== null)) {
            // загоняем массив из реквеста в переменную
            $user = $request->user;

            // обновляем данные в базе  из массива
            try{
                DB::table('users')
                    ->where('id', $user_id)
                    ->update($user);
            }
            catch (\Exception $e){
                return ResponseHelper::response_error_write_db();
            }
            
        }

        //если в job есть какая то информация
        if (isset($request->job) && ( $request->job !== null)){
            $jobs = $request->job;
            foreach ($jobs as $job){
                // если не передан id значит собираем запрос на вставку инфы в БД
                if ( $job["id"] == null ){
                    $job["user_id"] = $user_id;
          
                    // записываем новые данные в базу  из массива
                    try{
                        DB::table('users_job_places')
                            ->insert($job);
                    }
                    catch (\Exception $e){
                        return ResponseHelper::response_error_write_db();
                    }
                }
                // если id передан,  то смотрим дальше или  апдейтить запись или удалять
                if ( $job["id"] !== null ){
                    // если job_oid равен null, то нужно удалить эту строку из БД по id записи строки
                    // если вдруг id будет передано как null  то возникает экзепшен
                    if ($job["job_oid"] == null){
                        try{
                            DB::table('users_job_places')
                                ->where(['id'=>$job["id"], 'user_id'=>$user_id])
                                ->delete();
                        }
                        catch (\Exception $e){
                            return ResponseHelper::response_error_delete_from_db();
                        }
                    }
                    // если job_oid заполнен то апдейтим запись в БД по id  записи
                    else{
                        // обновляем запись в БД
                        try{
                            DB::table('users_job_places')
                                ->where(['id'=>$job["id"], 'user_id'=>$user_id])
                                ->update($job);
                        }
                        catch (\Exception $e){
                            return ResponseHelper::response_error_write_db();
                        }
                    } // -- end else --
                } // -- end if --
                
            } // -- end foreach
        } // -- end if ( $request->job !== null)

        //если в spec есть какая то информация
        if (isset($request->spec) && ( $request->spec !== null)){
            $specs = $request->spec;
            foreach ($specs as $spec){
                // если не передан id значит собираем запрос на вставку инфы в БД
                if ( $spec["id"] == null ){
                    $spec['user_id'] = $user_id;
                    // записываем новые данные в базу  из массива
                    try{
                        DB::table('users_specializations')
                            ->insert($spec);
                    }
                    catch (\Exception $e){
                        return ResponseHelper::response_error_write_db();
                    }
                }
                // если id передан,  то смотрим дальше или  апдейтить запись или удалять
                if ( $spec["id"] !== null ){
                    // если spec_id равен null, то нужно удалить эту строку из БД по id записи строки
                    // если вдруг id будет передано как null  то возникает экзепшен
                    if ($spec["spec_id"] == null){
                        try{
                            DB::table('users_specializations')
                                ->where(['id'=>$spec["id"], 'user_id'=>$user_id]) 
                                ->delete();   
                        }
                        catch (\Exception $e){
                            return ResponseHelper::response_error_delete_from_db();
                        }
                    }
                    // если spec_id заполнен то апдейтим запись в БД по id  записи
                    else{
                        // обновляем запись в БД
                        try{
                            DB::table('users_specializations')
                                ->where(['id'=>$spec["id"], 'user_id'=>$user_id]) 
                                ->update($spec);
                        }
                        catch (\Exception $e){
                            return ResponseHelper::response_error_write_db();
                        }
                    } // -- end else --
                } // -- end if --
                
            } // -- end foreach
        } // -- end if ( $request->spec !== null)

        //если в interests есть какая то информация
        if (isset($request->interests) && ( $request->interests !== null)){
            $interests = $request->interests;
            // если массив пустой, то значит удаляем все интересы пользователя из бд
            if (count($interests) == 0){
                try{
                    DB::table('users_sc_interests')
                        ->where(['user_id'=>$user_id]) 
                        ->delete();   
                }
                catch (\Exception $e){
                    return ResponseHelper::response_error_delete_from_db();
                }
            } // -- end if
            else{
                $new_user_data = array();
                foreach ($interests as $interest){
                    $new_user_data[] = array(
                        'user_id'     => $user_id,
                        'interest_id' => $interest
                    );
                }
                //очищаем записи с интересами пользователя 
                try{
                    DB::table('users_sc_interests')
                        ->where(['user_id'=>$user_id]) 
                        ->delete();   
                }
                catch (\Exception $e){
                    return ResponseHelper::response_error_delete_from_db();
                }
                //пишем в таблицу новые данные
                try{
                    DB::table('users_sc_interests')
                    ->insert($new_user_data);  
                }
                catch (\Exception $e){
                    return ResponseHelper::response_error_write_db();
                }

            }
        }// -- end if ( $request->interests !== null)
        
        return ResponseHelper::response_ok(['status'=>'success']);
    } // -- end function profile_updater


    /*
        -------------------------------
                profile_getter
        -------------------------------
    */
    # отдает полную информацию о профиле пользователя
    public function profile_getter(Request $request, $userId = NULL){
        /*
        // валидируем тип заголовка запроса
        $result = HeaderJsonTypeValidator::validate_json_header($request);
        if ($result !== true){ return $result; }

        // проверка токена
        $result = HeaderTokenValidator::validate_token($request);
        if ( is_int($result) == false){ return $result; }
        else {$user_id = $result;} // берем юзер ид из ответа валидатора
        */

        // если передан ид искомого пользователя
        if ($userId !== Null){
            // то берем и используем далее запрашиваемый ид
            $user_id = $userId;
        }
        else{
            // если ид не запрошен, то есть юзер хочет свой профиль, то
            // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
            $user_id = $request->user_id;
        }
        
    
        // инициализируем выходной массив
        $full_user_unfo = array();

        // получаем инфу из таблицы user
        try{
            $user=DB::table('users')
                ->leftJoin('cities', 'users.city_id', '=','cities.id' )
                ->leftJoin('regions', 'cities.addrRegionId', '=','regions.regionId' )
                ->select('users.id', 'users.first_name', 'users.last_name', 'users.middle_name', 'users.email', 'users.phone_number',
                        'users.birthday','users.city_id','cities.cityName', 'regions.regionId', 'regions.regionName', 'users.foto')
                ->where(['users.id'=>$user_id]) 
                ->first(); 
            
            if ( $user == [] ){
                $text = 'user not found';
                return ResponseHelper::custom_response($text, 404);
            }



        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }
        $full_user_unfo['user']=$user;
        

        // получаем инфу из таблицы о работе пользователя
        try{
            $job=DB::table('users_job_places')
                ->leftJoin('med_orgs', 'med_orgs.oid', '=','users_job_places.job_oid' )
                ->select( 'users_job_places.id', 'users_job_places.job_oid', 'users_job_places.is_main', 'med_orgs.nameShort')
                ->where(['users_job_places.user_id'=>$user_id]) 
                ->get();
            /*
                SELECT ujp.id, ujp.job_oid, ujp.is_main, m.nameShort 
                FROM `users_job_places` AS ujp 
                LEFT JOIN med_orgs AS m 
                ON m.oid = ujp.job_oid 
                WHERE ujp.user_id=25
            */
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }
        // преобразуем поле is_main из 1/0 в true/false
        foreach ($job as $j){
            $j->is_main =  $j->is_main ? true : false;
        }
        $full_user_unfo['job']=$job;

        // получаем инфу из таблицы специальностей пользователя
        try{
            $spec=DB::table('users_specializations as us')
                ->leftJoin('specializations as s', 's.id', '=','us.spec_id' )
                ->select( 'us.id', 'us.spec_id', 'us.is_main', 
                            's.id as spec_id', 's.code', 's.name')
                ->where(['us.user_id'=>$user_id])
                ->get();
            /*
                SELECT us.id, us.spec_id, us.is_main, s.id as spec_id, s.code, s.name 
                FROM `users_specializations` AS us 
                LEFT JOIN specializations AS s 
                ON s.id = us.spec_id 
                WHERE us.user_id=25
            */
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }
        
        // преобразуем поле is_main из 1/0 в true/false
        foreach ($spec as $s){
            $s->is_main =  $s->is_main ? true : false;
        }
        $full_user_unfo['spec']=$spec;

        // получаем инфу из таблицы интересов пользователя
        try{
            $interests = DB::table('users_sc_interests as u_sc_i')
                ->leftJoin('scientific_interests as s_i', 's_i.id', '=', 'u_sc_i.interest_id')
                ->select('u_sc_i.interest_id', 's_i.specialization_code as spec_code', 's_i.name')
                ->where(['u_sc_i.user_id'=>$user_id])
                ->get();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }
        $full_user_unfo['interests']=$interests;

        // добавляем поле "in_contacts", где 1 - пользователь добавлен в конаткы, 0 - нет.

        if ($userId !== null){
            // проверяем, добавлен ли пользователь в контакты
            try {   
                $result = DB::table('contact_groups as cg')
                    ->leftJoin('contacts_and_groups_connection as cagc', 'cagc.contact_group_id', '=', 'cg.id')
                    ->leftJoin('contacts as c', 'c.id', '=', 'cagc.contact_id')
                    ->select('participant_id') 
                    ->where('group_owner_id', $request->user_id)
                    ->whereNull('group_name')
                    ->where('participant_id', $user_id)
                    ->get();
            }
            catch (\Exception $e) {
                return ResponseHelper::response_error_read_db();
            }

            // добавляем поле "in_contacts", где 1 - пользователь добавлен в конаткы, 0 - нет.
            if ($result->isNotEmpty()) {
                $full_user_unfo['in_contacts'] = 1;
            }
            else {
                $full_user_unfo['in_contacts'] = 0;
            }
        }
        
        return ResponseHelper::response_ok($full_user_unfo);
    } // -- end function profile_getter


    /*
        ------------------------------
                interest_add
        ------------------------------

        REQUEST:  POST
        header:  -- *required
        Content-Type:application/json 
        X-Auth-Token:20_xfy5kURi42yx5n8qC8hwYdA5KwEys1gsu9fYloCvFbqviTHeYpVajoeS5bBsN6oE 
        body: {"interest": "что-то новое"}
    */
    # добавление пользовательского интереса в профиле пользователя
    public function interest_add(Request $request){
        //если в interest есть какая то информация
        if (isset($request->interest) && ($request->interest !== null)) {
            // передаём значение в переменную
            $new_interest = $request->interest;
        } 
        else {
            $text = 'field interest not be empty';
            return ResponseHelper::custom_response($text, 400);
        }

        // проверяем нет ли уже такого интереса в БД
        try{
            $interests = DB::table('scientific_interests')
                ->select('id', 'name')
                ->whereRaw('LOWER(name) LIKE ?', ['%'.$new_interest.'%'])
                ->get();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }
        
        // если есть одно или несколько совпадений, возвращаем их пользователю для выбора
        if ($interests->isNotEmpty()){
            return response($interests, 409);  // http 409 Conflict («конфликт»)  пока идея такого ответа
        }

        // если нет совпадений
        // получаем id юзера из реквеста
        $user_id = $request->user_id;
        // добавляем интерес в БД и в список интересов пользователя
        try{
            $new_interest_id = DB::table('scientific_interests')
                ->insertGetId(['name' => $new_interest]);

            DB::table('users_sc_interests')
                ->insert(['user_id' => $user_id, 'interest_id' => $new_interest_id]);
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_write_db();
        }
        // возвращаем id и название интереса
        $result = ['id' => $new_interest_id, 'name' => $new_interest];
        
        return ResponseHelper::response_ok($result);
        
    }
} // -- end class  UserController