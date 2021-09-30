<?php

namespace App\Helpers;

class NewPasswordGenerator {

    public static function random_password($pass_length){
        // Символы, которые будут использоваться в пароле.
        $chars="qazxswedcvfrtgbnhyujmkiolp1234567890QAZXSWEDCVFRTGBNHYUJMKIOLP~#@!%$?=()";
        // Количество символов в пароле.
        $max=$pass_length;
        // Определяем количество символов в $chars
        $size=StrLen($chars)-1;
        // Определяем пустую переменную, в которую и будем записывать символы.
        $password=null;
        // Создаём пароль.
        while($max--)
            $password.=$chars[rand(0,$size)];
        // Возвращаем созданный пароль.
        return $password;
    }
}
