<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 20.
 * Time: 14:59
 */

namespace KodiApp\ServiceProvider;


use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class MonologProvider implements ServiceProviderInterface
{
    private $monologConfig;
    /**
     * MonologProvider constructor.
     * @param array $config
     */
    public function __construct($config)
    {
        $this->monologConfig = $config;
    }


    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Container $pimple A container instance
     */
    public function register(Container $pimple)
    {
        $configuration = $this->monologConfig;
        $pimple['logger'] = function ($c) use($configuration) {
            $logger = new Logger($configuration["name"]);
            $logger->pushHandler(new StreamHandler($configuration["path"], $configuration["log_level"]));
            return $logger;
        };
    }
}