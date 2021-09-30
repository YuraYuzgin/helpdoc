<?php

namespace App\Http\Controllers\UserProfile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;

use App\Helpers\ResponseHelper;

/*

{
    "first_name": "Наталья",
    "middle_name": "Ивановна",
    "last_name": "Жукова",
    "email": "zhukova@mail.ru",
    "phone_number": "79001234567",
    "age_from": 20,
    "age_to": 30,
    "city_id": 355,
    "job_places": ["1.2.643.5.1.13.13.12.2.61.6141", "1.2.643.5.1.13.13.12.2.61.6142"],
    "specializations": [5, 9],
    "scientific_interests": [25, 26]
    "page":1,  *requred
    "limit":20  *requred
}

*/


class SearchUserController extends Controller{
    /*
        -------------------------------
                search_users
        -------------------------------
    */
    # отдает результаты поиска пользователей 
    public function search_users(Request $request){

        // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
        $user_id = $request->user_id;  // не факт что тут будет нужно, но на всякий случай

        //если в json-e отсутствует или равняется null поле номера страницы
        if (  !(isset($request->page) && ($request->page !== null)) ) {
            $text = 'field page is missing or has an incorrect value';
            return ResponseHelper::custom_response($text, 403);
        }

        //если в json-e отсутствует или равняется null или равняется 0 поле количества выдаваемых на страницу пользователей
        if (  !(isset($request->limit) && ($request->limit !== null) && ($request->limit !== 0)) ) {
            $text = 'field limit is missing or has an incorrect value';
            return ResponseHelper::custom_response($text, 403);
        }

        try{
            $db_connection_pdo = DB::connection()->getPdo();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }
        // Начало формирования SQL.
        // $sql_count -- запрос на подсчёт количества строк, удовлетворяющих поисковому запросу (нужно для постраничной навигации).
        // $sql_search -- запрос на получения данных по поисковому запросу.
        // $sql -- общая часть SQL-запроса, использующаяся в $sql_count и $sql_search.

        $sql_count = "SELECT COUNT(*) AS `total` FROM `users` u";
        $sql_search = "SELECT u.id AS `user_id`, u.first_name, u.middle_name, u.last_name, u.foto FROM `users` u";

        $sql = "";

        $where = array(
            //если убрать эту проверку, то ниже в коде надо раскомментить if
            "(u.id <> " . (int)$user_id . ")"
        );

        if ( isset($request->first_name) && ($request->first_name !== null) && ($request->first_name !== '') ) {
            $first_name = str_replace(array('^', '%', '_'), array('^^', '^%', '^_'), $request->first_name) . '%';
            $where[] = "(u.first_name LIKE " . $db_connection_pdo->quote($first_name) . " ESCAPE '^')";
        }
        if (isset($request->middle_name) && ($request->middle_name !== null) && ($request->middle_name !== '') ) {
            $middle_name = str_replace(array('^', '%', '_'), array('^^', '^%', '^_'), $request->middle_name) . '%';
            $where[] = "(u.middle_name LIKE " . $db_connection_pdo->quote($middle_name) . " ESCAPE '^')";
        }
        if (isset($request->last_name) && ($request->last_name !== null) && ($request->last_name !== '') ) {
            $last_name = str_replace(array('^', '%', '_'), array('^^', '^%', '^_'), $request->last_name) . '%';
            $where[] = "(u.last_name LIKE " . $db_connection_pdo->quote($last_name) . " ESCAPE '^')";
        }
        if (isset($request->email) && ($request->email !== null) && ($request->email !== '') ) {
            $where[] = "(u.email = " . $db_connection_pdo->quote($request->email) . ")";
        }
        if (isset($request->phone_number) && ($request->phone_number !== null) && ($request->phone_number !== '') ) {
            $where[] = "(u.phone_number = " . $db_connection_pdo->quote($request->phone_number) . ")";
        }
        if (isset($request->age_from) && ($request->age_from !== null) && ($request->age_from !== 0) ) {
            $where[] = "(DATE_ADD(u.birthday, INTERVAL " . (int)$request->age_from . " YEAR) <= CURDATE())";
        }
        if (isset($request->age_to) && ($request->age_to !== null) && ($request->age_to !== 0) ) {
            $where[] = "(DATE_SUB(DATE_ADD(u.birthday, INTERVAL " . ((int)$request->age_to + 1) . " YEAR), INTERVAL 1 DAY) >= CURDATE())";
        }
        if (isset($request->city_id) && ($request->city_id !== null) && ($request->city_id !== 0)) {
            $where[] = "(u.city_id = " . (int)$request->city_id . ")";
        }

        if (isset($request->job_places) && !(empty($request->job_places)) ) {
            $job_places = array();
            foreach ($request->job_places as $job_place) {
                $job_places[] = $db_connection_pdo->quote($job_place);
            }

            $where[] = "((SELECT COUNT(*) FROM `users_job_places` ujp WHERE (ujp.user_id = u.id) AND (ujp.job_oid IN (" . implode(", ", $job_places) . "))) > 0)";
        }

        if (isset($request->specializations) && !(empty($request->specializations)) ) {
            $specializations = array();
            foreach ($request->specializations as $specialization) {
                $specializations[] = (int)$specialization;
            }
            
            $where[] = "((SELECT COUNT(*) FROM `users_specializations` us WHERE (us.user_id = u.id) AND (us.spec_id IN (" . implode(", ", $specializations) . "))) > 0)";
        }

        if (isset($request->scientific_interests) &&  !(empty($request->scientific_interests)) ) {
            $scientific_interests = array();
            foreach ($request->scientific_interests as $scientific_interest) {
                $scientific_interests[] = (int)$scientific_interest;
            }
            
            $where[] = "((SELECT COUNT(*) FROM `users_sc_interests` usi WHERE (usi.user_id = u.id) AND (usi.interest_id IN (" . implode(", ", $scientific_interests) . "))) > 0)";
        }
        
        // раскомментить иф если самое верхнее $where будет пустым, т.е. $where = array();
        // if ($where) {
            $sql .= (" WHERE " . implode(" AND ", $where));
        // }

        $sql_count .= $sql;
        $sql_search .= $sql;

        $sql_search .= " ORDER BY (CASE WHEN u.last_name IS NULL THEN 0 ELSE 1 END) DESC, u.last_name ASC, (CASE WHEN u.first_name IS NULL THEN 0 ELSE 1 END) DESC, u.first_name ASC, u.middle_name ASC LIMIT " . ($request->page - 1) * $request->limit . ", " . $request->limit;
        
        // Конец формирования SQL.
        // echo $request . "\r\n\r\n--- sql_count ---\r\n" . $sql_count . "\r\n\r\n--- sql_search ---\r\n" . $sql_search;
        $result = array();
        try{

            // кидаем запрос и смотрим количество найденных пользователей
            $search_count = DB::select($sql_count);

            // если найденных ноль, то так и отвечаем
            
            if ($search_count[0]->total== 0){
                $result['users'] = [];
            }
            else{
                // если нашлось больше нуля, то
                // кидаем запрос и получаем всех искомых пользователей
                $search_users = DB::select($sql_search); 
                //делаем из полученного объекта привычный массив
                $array_users = json_decode(json_encode($search_users), True, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $result['users'] = $array_users;
                
                // Собираем в отдельный массив ID всех найденных пользователей.
                $found_users_ids = array();
                foreach ($result['users'] as $user) {
                    $found_users_ids[] = (int)$user['user_id'];
                }
                
                // Получаем места работы найденных пользователей и группируем данные по user_id.
                $search_job_places = DB::select("SELECT ujp.user_id, ujp.is_main, mo.oid, mo.nameShort FROM `users_job_places` ujp LEFT JOIN `med_orgs` mo ON (ujp.job_oid = mo.oid) WHERE ujp.user_id IN (" . implode(', ', $found_users_ids) . ")");
                $array_job_places = json_decode(json_encode($search_job_places), True, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $job_places_by_users = array();
                foreach ($array_job_places as $job_place) {
                    $user_id = (int)$job_place['user_id'];
                    
                    if (!isset($job_places_by_users[$user_id])) {
                        $job_places_by_users[$user_id] = array();
                    }
                    $job_places_by_users[$user_id][] = array(
                        'oid' => $job_place['oid'],
                        'nameShort' => $job_place['nameShort'],
                        'is_main' => $job_place['is_main'] ? true : false
                    );
                }
                
                // Получаем специализации найденных пользователей и группируем данные по user_id.
                $search_specialisations = DB::select("SELECT us.user_id, s.id AS `spec_id`, s.name, us.is_main FROM `users_specializations` us LEFT JOIN `specializations` s ON (us.spec_id = s.id) WHERE us.user_id IN (" . implode(', ', $found_users_ids) . ")");
                $array_specialisations = json_decode(json_encode($search_specialisations), True, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $specializations_by_users = array();
                foreach ($array_specialisations as $specialization) {
                    $user_id = (int)$specialization['user_id'];
                    
                    if (!isset($specializations_by_users[$user_id])) {
                        $specializations_by_users[$user_id] = array();
                    }

                    $specializations_by_users[$user_id][] = array(
                        'id' => $specialization['spec_id'],
                        'name' => $specialization['name'],
                        'is_main' => $specialization['is_main'] ? true : false
                    );
                }

                // не требуется для страницы вывода пользователей
                // Получаем интересы найденных пользователей и группируем данные по user_id.
                /*
                $search_scientific_interests = DB::select("SELECT us.user_id, s.id AS `interest_id`, s.name, s.specialization_code FROM `users_sc_interests` us LEFT JOIN `scientific_interests` s ON (us.interest_id = s.id) WHERE us.user_id IN (" . implode(', ', $found_users_ids) . ")");
                $array_scientific_interests = json_decode(json_encode($search_scientific_interests), True, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                $scientific_interests_by_users = array();
                foreach ($array_scientific_interests as $scientific_interest) {
                    $user_id = (int)$scientific_interest['user_id'];
                    
                    if (!isset($scientific_interests_by_users[$user_id])) {
                        $scientific_interests_by_users[$user_id] = array();
                    }

                    $scientific_interests_by_users[$user_id][] = array(
                        'id' => $scientific_interest['interest_id'],
                        'name' => $scientific_interest['name'],
                        'specialization_code' => $scientific_interest['specialization_code'] 
                    );
                }
                */

                // Проходим по массиву $result['users'] и добавляем информацию о местах работы из $job_places_by_users
                // информацию о специализациях из $specializations_by_users.
                // информацию о интересах из $scientific_interests_by_users. -- не требуется 
                foreach ($result['users'] as &$user) {
                    $user_id = $user['user_id'];
                    if (isset($job_places_by_users[$user_id])) {
                        $user['job_places'] = $job_places_by_users[$user_id];
                    } else {
                        $user['job_places'] = array();
                    }

                    if (isset($specializations_by_users[$user_id])) {
                        $user['specializations'] = $specializations_by_users[$user_id];
                    } else {
                        $user['specializations'] = array();
                    }

                    // if (isset($scientific_interests_by_users[$user_id])) {
                    //     $user['scientific_interests'] = $scientific_interests_by_users[$user_id];
                    // } else {
                    //     $user['scientific_interests'] = array();
                    // }
                }
                unset($user);
            }


            //===================================================================================================

            // делаем пометку, кто из найденных пользователей добавлен в контакты
            // в "users" добавляется поле "in_contacts", где 1 - добавлен в контакты, 0 - не добавлен в контакты.
            
            // создаём массив из id польователей
            $id_array = array();
            foreach ($array_users as $key => $value) {
                foreach ($value as $k => $v) {
                    if ($k == 'user_id') $id_array[] = $v;
                }
            }
            
            // находим id всех пользователей, которые добавлены в список контактов
            try {   
                $participants_id = DB::table('contact_groups as cg')
                    ->leftJoin('contacts_and_groups_connection as cagc', 'cagc.contact_group_id', '=', 'cg.id')
                    ->leftJoin('contacts as c', 'c.id', '=', 'cagc.contact_id')
                    ->select('participant_id') 
                    ->where('group_owner_id', $request->user_id)
                    ->whereIn('participant_id', $id_array)
                    ->whereNull('group_name')
                    ->get();
            }
            catch (\Exception $e) {
                return ResponseHelper::response_error_read_db();
            }

            // переводим результат запроса в БД в ассоциативный массив
            $participants_id = json_decode($participants_id, true);

            // добавляем поле "in_contacts" и помечаем тех, кто добавлен в контакты
            foreach ($result['users'] as $key => $value) {
                $result['users'][$key]['in_contacts'] = 0;
                foreach ($value as $k => $v) {
                    if ($k == 'user_id'){
                        foreach ($participants_id as $key2 => $value2) {
                            foreach ($value2 as $k2 => $v2) {
                                if ($v === $v2) $result['users'][$key]['in_contacts'] = 1;
                            }
                        }
                    }
                }
            }
            
            //==================================================================================================


            $result['size'] = $search_count[0]->total; /// или вот так $items = json_decode(json_encode($search_count),true);
             
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }

        return ResponseHelper::response_ok($result);
    }
}