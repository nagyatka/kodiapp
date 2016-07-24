<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 20.
 * Time: 15:27
 */

namespace KodiApp\ServiceProvider;


use KodiApp\Security\Security;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class SecurityProvider implements ServiceProviderInterface
{
    /**
     * @var array
     */
    private $configuration;

    /**
     * SecurityProvider constructor.
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
        $config = $this->configuration;
        $pimple['security'] = function ($c) use($config) {
            $logger = new Security($c["logger"],$config);
            return $logger;
        };
    }
}