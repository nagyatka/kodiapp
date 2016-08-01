<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 01.
 * Time: 17:12
 */

namespace KodiApp\ServiceProvider;


use KodiApp\Heartbeat\Heartbeat;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class HeartbeatProvider implements ServiceProviderInterface
{

    public function register(Container $pimple)
    {
        $pimple['heartbeat'] = function ($c) {
            return new Heartbeat();
        };
    }
}