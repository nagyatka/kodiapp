<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 23.
 * Time: 20:45
 */

namespace KodiApp\ContentProvider;


use KodiApp\Application;

class ServerContentProvider implements ContentProvider
{

    public function getKey()
    {
        return "server";
    }

    public function getValue()
    {
        return [
            "server_name"       => $_SERVER["SERVER_NAME"],
            "server_protocol"   => ($_SERVER["SSL_PROTOCOL"]?'https':'http'),
            "session_token"     => Application::getInstance()->getSession()->get("session_token"),
            "environment"       => Application::getInstance()->getEnvironment()
        ];
    }
}