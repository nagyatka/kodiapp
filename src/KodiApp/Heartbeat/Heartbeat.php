<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 01.
 * Time: 16:59
 */

namespace KodiApp\Heartbeat;


class Heartbeat
{

    /**
     * @var HeartbeatListener[]
     */
    private $listeners;

    /**
     * Heartbeat constructor.
     */
    public function __construct()
    {
        $this->listeners = [];
    }


    public function addListener(HeartbeatListener $listener) {
        $this->listeners[] = $listener;
    }

    public function getResult() {
        $result = [];
        foreach ($this->listeners as $listener) {
            $result[$listener->getKey()] = $listener->getValue();
        }
        return $result;
    }
}