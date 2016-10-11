<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 24.
 * Time: 13:47
 */

namespace KodiApp\Exception;


class HttpAccessDeniedException extends HttpException
{

    /**
     * HttpAccessDeniedException constructor.
     */
    public function __construct()
    {
        http_response_code(403);
        parent::__construct(403);
    }
}