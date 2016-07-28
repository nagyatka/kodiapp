<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 23.
 * Time: 20:43
 */

namespace KodiApp\ContentProvider;


interface ContentProvider
{
    /**
     * @return string
     */
    public function getKey();

    /**
     * @return mixed
     */
    public function getValue();
}