<?php

use Illuminate\Http\Request;
use App\Http\Middleware;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/
/*
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});
*/

# регистрация
Route::post('/registration', 'RegistrationController@create_account')->middleware('check.header');
# удаление пользователя 
Route::post('/registration/del', 'RegistrationController@delete_account')->middleware('check.header','check.token');;
# удаление пользователя (временное, для ручного тестирования юзеров)
Route::get('/registration/del_by_emeil/{email}', 'RegistrationController@delete_account_by_emeil');
# восстановление пароля
Route::post('/recovery', 'RegistrationController@recovery_account_password')->middleware('check.header');
# вход пользователя
Route::post('/auth/login', 'CustomAuth\AuthController@login_user')->middleware('check.header');
# разлогинивание
Route::post('/auth/logout', 'CustomAuth\AuthController@logout_user')->middleware('check.header','check.token');


# отдача списка субъектов РФ ( регионов )
Route::get('/profile/regions', 'UserProfile\ResidenceController@get_regions');
# отдача списка городов субъекта РФ ( регионов )
Route::get('/profile/cities/{regionId}', 'UserProfile\ResidenceController@get_cities');
# отдача списка места работы 
Route::get('/profile/works/{regionId}', 'UserProfile\ResidenceController@get_works');
# отдача списка мед специализаций
Route::get('/profile/specializations', 'UserProfile\ResidenceController@get_specializations');
# отдача списка научных интересов
Route::get('/profile/sc_interests/{spec_code?}/{add_spec_code?}', 'UserProfile\ResidenceController@get_scientific_interests');
// Route::get('/profile/sc_interests/', 'UserProfile\ResidenceController@get_scientific_interests');
Route::get('/profile/sc_interests_speccode1/{spec_code}', 'UserProfile\ResidenceController@get_scientific_interests');
Route::get('/profile/sc_interests_speccode2/{spec_code}/{add_spec_code}', 'UserProfile\ResidenceController@get_scientific_interests');
# проверка на заполненность профиля
Route::post('/profile/check', 'UserProfile\UserController@profile_checker')->middleware('check.header','check.token');
# обновление записей в профиле
Route::post('/profile/update', 'UserProfile\UserController@profile_updater')->middleware('check.header','check.token');
# получение полной информации профиля
Route::post('/profile/get/{userId?}', 'UserProfile\UserController@profile_getter')->middleware('check.header','check.token');
# добавление пользовательского интереса
Route::post('/profile/sc_interests/add', 'UserProfile\UserController@interest_add')->middleware('check.header','check.token');


# создание и обновление события пользователя в календаре
Route::post('/event/set', 'Events\UserEventController@set_event')->middleware('check.header','check.token');  // -- работает
# отдача всех событий за определенную дату
Route::get('/event/date/{year}-{month}-{day}', 'Events\UserEventController@get_event_on_date')->middleware('check.token');
# удаление события
Route::get('/event/del/{event_id}', 'Events\UserEventController@delete_event')->middleware('check.token');
# получение конкретного события по id
Route::get('/event/get/{event_id}', 'Events\UserEventController@get_event')->middleware('check.token');


# поиск пользователей
Route::post('/seach/users', 'UserProfile\SearchUserController@search_users')->middleware('check.header','check.token');


# добавление группы контактов
Route::post('/contacts/add_group', 'UserProfile\ContactController@add_group')->middleware('check.header','check.token');
# редкатирование названия группы контактов
Route::post('/contacts/edit_group', 'UserProfile\ContactController@edit_group')->middleware('check.header','check.token');
# удаление группы контактов
Route::post('/contacts/del_group', 'UserProfile\ContactController@del_group')->middleware('check.header','check.token');
# получение всех групп контактов пользователя
Route::post('/contacts/get_groups', 'UserProfile\ContactController@get_groups')->middleware('check.header','check.token');

# добавление нового контакта в список контактов пользователя
Route::post('/contacts/add_contact', 'UserProfile\ContactController@add_contact')->middleware('check.header','check.token');
# добавление контакта в группу
Route::post('/contacts/add_contact_in_group', 'UserProfile\ContactController@add_contact_in_group')->middleware('check.header','check.token');
# удаление контакта
Route::post('/contacts/del_contact', 'UserProfile\ContactController@del_contact')->middleware('check.header','check.token');
# удаление контакта из группы
Route::post('/contacts/del_contact_from_group', 'UserProfile\ContactController@del_contact_from_group')->middleware('check.header','check.token');
# отдача всех контактов пользователя
Route::post('/contacts/get_contacts', 'UserProfile\ContactController@get_contacts')->middleware('check.header','check.token');

# добавление, изменение и удаление комментария к контакту
Route::post('/contacts/comment', 'UserProfile\ContactController@comment')->middleware('check.header','check.token');
# отдача комментария к контакту
Route::post('/contacts/get_comment', 'UserProfile\ContactController@get_comment')->middleware('check.header','check.token');




// ======================
//  примеры 
// ======================

// Route::post('/event/create',[
//     'middleware' => ['check.header','check.token'],  // -- такой вызов работает, но немного громоздкий
//     'uses' => 'Events\EventController@create_event'
//     ]);
// Route::post('/event/create', 'Events\EventController@create_event', 'check.header');  // -- такой вызов не отрабатывает
// Route::post('/event/create', 'Events\EventController@create_event')->middleware('check.header')->middleware('check.token');  // -- работает