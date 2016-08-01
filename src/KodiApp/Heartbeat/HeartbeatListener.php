<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 01.
 * Time: 17:00
 */

namespace KodiApp\Heartbeat;


interface HeartbeatListener
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