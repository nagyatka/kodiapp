<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 28.
 * Time: 20:27
 */

namespace KodiApp\Response;


use KodiApp\Application;

class ErrorResponse extends Response
{
    public function __construct($status,$content = null)
    {
        parent::__construct(
            Application::isDevelopmentEnv() && $content != null ? $content : Response::$statusTexts[$status], $status);
    }


}