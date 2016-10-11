<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 24.
 * Time: 13:48
 */

namespace KodiApp\Exception;


class HttpInternalErrorException extends HttpException
{

    /**
     * HttpInternalErrorException constructor.
     */
    public function __construct()
    {
        http_response_code(500);
        parent::__construct(500);
    }
}