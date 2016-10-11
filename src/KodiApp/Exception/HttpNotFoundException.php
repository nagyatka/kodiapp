<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 24.
 * Time: 13:46
 */

namespace KodiApp\Exception;


class HttpNotFoundException extends HttpException
{


    /**
     * HttpNotFoundException constructor.
     */
    public function __construct()
    {
        http_response_code(404);
        parent::__construct(404);
    }
}