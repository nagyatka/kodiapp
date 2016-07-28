<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 21.
 * Time: 16:48
 */

namespace KodiApp\ServiceProvider;

use KodiApp\Session\Session;
use KodiApp\Session\SessionStorage;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class SessionProvider implements ServiceProviderInterface
{
    private $configuration;

    /**
     * SessionProvider constructor.
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        $this->configuration = $configuration;
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
        $configuration = $this->configuration;
        Session::initSession($configuration);

        $pimple['session'] = function ($c) {
            return new SessionStorage();
        };
    }
}