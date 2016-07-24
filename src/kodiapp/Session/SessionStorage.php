<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 21.
 * Time: 16:43
 */

namespace KodiApp\Session;


class SessionStorage
{

    /**
     * SessionStorage constructor.
     */
    public function __construct()
    {
    }

    public function get($name,$defaultValue = null) {
        return isset($_SESSION[$name]) ? $_SESSION[$name] : $defaultValue;
    }

    public function set($name,$value,$defaultValue = null) {
        $_SESSION[$name] = $value != NULL ? $value : $defaultValue;
    }
}