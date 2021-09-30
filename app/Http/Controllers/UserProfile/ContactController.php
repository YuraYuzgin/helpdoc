<?php

namespace App\Http\Controllers\UserProfile;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use App\Helpers\ResponseHelper;
use Carbon\Carbon;


class ContactController extends Controller{

/*
=============================================================================
                                GROUPS
=============================================================================
*/

    /*
    -----------------------------
            add_group
    -----------------------------
    */

    # добавляет новую группу контактов в список групп контактов польователя
    public function add_group(Request $request){

        // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
        $user_id = $request->user_id;

        //если в group_name есть какая то информация
        if (isset($request->group_name) && ($request->group_name !== null)) {
            // передаём значение в переменную
            $group_name = $request->group_name;
        } 
        else {
            $text = 'field group_name not be empty';
            return ResponseHelper::custom_response($text, 400);
        }

        // проверяем, нет ли уже группы с таким названием у пользователя
        try{
            $result = DB::table('contact_groups')
                ->where([['group_owner_id', $user_id], ['group_name', $group_name]])
                ->first();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }
        // если такая группа существует, сообщаем об этом
        if ($result) {
            // не уверен в номере ошибки
            $text = 'group_name must be unique';
            return ResponseHelper::custom_response($text, 403);
        }

        // добавляем группу контактов в список групп контактов польователя
        try{
            DB::table('contact_groups')
                ->insert(['group_name' => $group_name, 'group_owner_id' => $user_id]);
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_write_db();
        }

        return ResponseHelper::response_ok(['status'=>'success']);
    }


    /*
    ----------------------------
            edit_group
    ----------------------------
    */

    # редактирует название группы контактов
    public function edit_group(Request $request){

        // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
        $user_id = $request->user_id;

        // передаём значение group_contacts_id в переменную
        $contact_group_id = $request->contact_group_id;

        //если в group_name есть какая то информация
        if (isset($request->group_name) && ($request->group_name !== null)) {
            // передаём значение в переменную
            $group_name = $request->group_name;
        } 
        else {
            $text = 'field group_name not be empty';
            return ResponseHelper::custom_response($text, 400);
        }

        // проверяем, нет ли уже группы с таким названием у пользователя
        try{
            $result = DB::table('contact_groups')
                ->where([['group_owner_id', $user_id], ['group_name', $group_name]])
                ->first();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }
        // если такая группа существует, сообщаем об этом
        if ($result) {
            // не уверен в номере ошибки
            $text = 'group_name must be unique';
            return ResponseHelper::custom_response($text, 403);
        }

        // меняем название группы контактов
        try{
            DB::table('contact_groups')
                ->where('id', $contact_group_id)
                ->update(['group_name' => $group_name]);
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_write_db();
        }

        return ResponseHelper::response_ok(['status'=>'success']);
    }


    /*
    ---------------------------
            del_group
    ---------------------------
    */

    # удаляет группу контактов и привязки контактов к ней по переданному id
    public function del_group(Request $request){

        // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
        $user_id = $request->user_id;

        // передаём значение group_contacts_id в переменную
        $contact_group_id = $request->contact_group_id;

        // ! возможно, лишняя проверка !
        // проверяем не запрашивается ли удаление базовой группы
        try{
            $group_null_id = DB::table('contact_groups')
                ->select('id')
                ->where('group_owner_id', $user_id)
                ->whereNull('group_name')
                ->first();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }
        
        if($group_null_id->id == $contact_group_id){
            $text = 'cannot delete base group';
            return ResponseHelper::custom_response($text, 400);
        }

        // удаляем группу контактов и привязки контактов к ней
        try{
            DB::transaction(function () use ($contact_group_id) {
                DB::table('contact_groups')
                    ->where('id', $contact_group_id)
                    ->delete();
            
                DB::table('contacts_and_groups_connection')
                    ->where('contact_group_id', $contact_group_id)
                    ->delete();
            }, 3);
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_delete_from_db();
        }

        return ResponseHelper::response_ok(['status'=>'success']);

    }

    /*
    ---------------------------
            get_groups
    ---------------------------
    */

    # отдаёт все группы контактов польователя, где null - имя базовой группы
    public function get_groups(Request $request){

        // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
        $user_id = $request->user_id;
        
        // ищем в БД все группы пользователя
        try {
            $contact_groups = DB::table('contact_groups')
                ->select('id', 'group_name')
                ->where('group_owner_id', $user_id)
                // условие для исключения из выборки базовой группы единичных контактов
                ->whereNotNull('group_name')
                ->get();
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_read_db();
        }

        return ResponseHelper::response_ok($contact_groups);
    }


/*
=============================================================================
                                CONTACTS
=============================================================================
*/

    /*
        ----------------------------
                add_contact
        ----------------------------
    */

    # добавляет новый контакт в список контактов пользователя
    public function add_contact(Request $request){

        // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
        $user_id = $request->user_id;

        // передаём значение participant_id
        $participant_id = $request->participant_id;

        // нельзя добавить себя в свой список контактов
        // может, это лишняя проверка
        if ($user_id === $participant_id){
            // не уверен в номере ошибки
            $text = 'user cannot add yourself to contacts';
            return ResponseHelper::custom_response($text, 403);
        }
        
        // проверяем добавлен ли уже participant_id в список контактов
        // или это лишняя проверка? В профиле уже добавленного пользователя в будущем не должно быть возможности добавить ещё раз
        try{
            $result = DB::table('contact_groups as cg')
                ->leftJoin('contacts_and_groups_connection as cagc', 'cagc.contact_group_id', '=', 'cg.id')
                ->leftJoin('contacts as c', 'c.id', '=', 'cagc.contact_id')
                ->where('group_owner_id', $user_id)
                ->where('group_name', null)
                ->where('participant_id', $participant_id)
                ->get();
        }
        catch (\Exception $e){
            return ResponseHelper::response_error_read_db();
        }
        // если такой контакт существует, сообщаем об этом
        if ($result->isNotEmpty()) {
            // не уверен в номере ошибки
            $text = 'user must be unique';
            return ResponseHelper::custom_response($text, 403);
        }

        // получаем id группы null пользователя.
        try{
            $groups_id = DB::table('contact_groups')
                ->select('id')
                ->where('group_owner_id', $user_id)
                ->whereNull('group_name')
                ->first();
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_read_db();
        }

        // передаём id группы в переменную
        $group_id = $groups_id->id;

        // записываем в БД контакт и привязку к базовой группе
        try{
            DB::transaction(function () use ($participant_id, $group_id) {
                $contact_id = DB::table('contacts')
                    ->insertGetId(['participant_id' => $participant_id]);
    
                DB::table('contacts_and_groups_connection')
                    ->insert(['contact_group_id' => $group_id, 'contact_id' => $contact_id]);
            }, 3);
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_write_db();
        }  

        return ResponseHelper::response_ok(['status'=>'success']);
    }


    /*
        -------------------------------------
                add_contact_in_group
        -------------------------------------
    */

    # добавляет контакт в группу
    public function add_contact_in_group(Request $request){

        // передаём значения в переменные
        $contact_id = $request->contact_id;
        $contact_group_id = $request->contact_group_id;

        // проверяем нет ли уже переданного контакта в переданной группе
        try{
            $result = DB::table('contacts_and_groups_connection')
                ->where('contact_id', $contact_id)
                ->where('contact_group_id', $contact_group_id)
                ->first();
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_read_db();
        }
        if ($result !== null) {
            // не уверен в номере ошибки
            $text = 'contact already added to group';
            return ResponseHelper::custom_response($text, 403);
        }

        // добавляем контакт в группу
        try{
            DB::table('contacts_and_groups_connection')
                ->insert(['contact_group_id' => $contact_group_id , 'contact_id' => $contact_id]);
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_write_db();
        }

        return ResponseHelper::response_ok(['status'=>'success']);
    }


    /*
        -------------------------------------
                    del_contact
        -------------------------------------
    */

    # удаляет контакт
    public function del_contact(Request $request){

        // передаём значение в переменную
        $contact_id = $request->contact_id;

        // удаляем из БД контакт и её привязки группам
        try{
            DB::transaction(function () use ($contact_id) {
                DB::table('contacts')
                    ->where('id', $contact_id)
                    ->delete();

                DB::table('contacts_and_groups_connection')
                    ->where('contact_id', $contact_id)
                    ->delete();
            }, 3);
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_delete_from_db();
        }

        return ResponseHelper::response_ok(['status'=>'success']);
    }


    /*
        -------------------------------------
                del_contact_from_group
        -------------------------------------
    */

    # удаляет контакт из группы
    public function del_contact_from_group(Request $request){

        // передаём значения в переменные
        $contact_id = $request->contact_id;
        $group_id = $request->group_id;

        // удаляем контакт из группы
        try{
            DB::table('contacts_and_groups_connection')
                ->where('contact_group_id', $group_id)
                ->where('contact_id', $contact_id)
                ->delete();
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_delete_from_db();
        }

        return ResponseHelper::response_ok(['status'=>'success']);
    }


    /*
        -------------------------------------
                    get_contacts
        -------------------------------------
    */

    # отдаёт все контакты пользователя
    public function get_contacts(Request $request){
        
        // забираем из обновленного и провалидированного мидлварой реквеста ид пользователя
        $user_id = $request->user_id;

        // передаём значение в переменную
        $group_name = $request->group_name;
        
        // ищем в БД все контакты пользователя и собираем всю информацию, кроме специализаций
        try {
            $contacts = DB::table('contact_groups as cg')
                ->leftJoin('contacts_and_groups_connection as cagc', 'cagc.contact_group_id', '=', 'cg.id')
                ->leftJoin('contacts as c', 'c.id', '=', 'cagc.contact_id')
                ->leftJoin('users as u', 'u.id', '=', 'c.participant_id')
                ->select('contact_id', 'participant_id', 'last_name', 'first_name', 'middle_name',
                    'foto', 'phone_number', 'email') 
                ->where('group_owner_id', $user_id)
                ->where('group_name', $group_name)
                ->distinct()
                ->get();
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_read_db();
        }
        // Переводим результат запроса в БД в ассоциативный массив
        $array_contacts = json_decode($contacts, true);

        // переводим дату рождения в возраст и добавляем в массив вместо даты рождения
        // создаём массив из id пользователей
        $array_users_id = array();
        foreach ($array_contacts as $key => $value) {
            foreach ($value as $k => $v) {
                if ($k == 'birthday'){
                    $age = Carbon::parse($v)->age;
                    if($age == 0) $age = null;
                    $array_contacts[$key]['age'] = $age;
                }
                if ($k == 'participant_id'){
                    $array_users_id[] = $v;
                }
                
            }
            unset($array_contacts[$key]['birthday']);
            // создаём поле под главные специализации
            $array_contacts[$key]['specialization'] = null;
        }

        // ищем в БД главные специализации отобранных пользователей
        try {
            $specializations = DB::table('users_specializations as us')
                ->leftJoin('specializations as sp', 'sp.id', '=', 'us.spec_id')
                ->select('user_id', 'name')
                ->whereIn('user_id', $array_users_id)
                ->where('is_main', 1)
                ->get();
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_read_db();
        }
        // Переводим результат запроса в БД в ассоциативный массив
        $array_spec = json_decode($specializations, true);

        // соединяем 2 массива
        foreach ($array_contacts as $key => $value) {
            foreach ($array_spec as $key2 => $value2) {
                if($array_contacts[$key]['participant_id'] == $array_spec[$key2]['user_id']){
                    $array_contacts[$key]['specialization'] = $array_spec[$key2]['name'];
                }

            }
        }

        return ResponseHelper::response_ok($array_contacts);
    }




/*
=============================================================================
                                COMMENTS
=============================================================================
*/


    /*
        ------------------------
                comment
        ------------------------
    */

    # добавляет, изменяет и удаляет комментарий
    public function comment(Request $request){

        // передаём значения в переменные
        $comment = $request->comment;
        $contact_id = $request->contact_id;

        // добавляем или изменяем комментарий комментарий
        try{
            DB::table('contacts')
                ->where('id', $contact_id)
                ->update(['comment' => $comment]);
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_write_db();
        }

        return ResponseHelper::response_ok(['status'=>'success']);
    }

    /*
        ---------------------------
                get_comment
        ---------------------------
    */

    # отдаёт комментарий
    public function get_comment(Request $request){

        // передаём contact_id в переменную
        $contact_id = $request->contact_id;

        // получаем из БД комментарий по переданному id контакта
        try{
            $comment = DB::table('contacts')
                ->select('id', 'comment')
                ->where('id', $contact_id)
                ->first();
        }
        catch (\Exception $e) {
            return ResponseHelper::response_error_read_db();
        }

        return ResponseHelper::response_ok($comment);
    }
}