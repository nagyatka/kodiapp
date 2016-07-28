<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 24.
 * Time: 13:42
 */

namespace KodiApp\Exception;


class HttpException extends \Exception
{
    private $errorCode;

    /**
     * HttpException constructor.
     * @param int $errorCode
     */
    public function __construct($errorCode)
    {
        $this->errorCode = $errorCode;
        parent::__construct("HTTP ERROR: ".$errorCode);
    }

}