<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 21.
 * Time: 16:58
 */

namespace KodiApp\Response;


use KodiApp\Application;

class JsonResponse extends Response
{

    /**
     * JsonResponse constructor.
     * @param array $values
     * @param int $options [optional]
     * @param int $status
     */
    public function __construct(array $values,$options = JSON_NUMERIC_CHECK, $status = 200)
    {
        parent::__construct(
            json_encode($values, $options),
            $status,
            [
                'Content-type: application/json',
            ]
        );
    }

}