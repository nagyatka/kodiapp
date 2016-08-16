<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 21.
 * Time: 17:50
 */

namespace KodiApp\ServiceProvider;



use KodiApp\Application;
use KodiApp\Security\Security;
use KodiApp\Twig\Twig;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class TwigProvider implements ServiceProviderInterface
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

        $pimple['twig'] = function ($c) use($configuration) {
            return new Twig($configuration);
        };

        //Jogosultság kezelése twighez
        $pimple->extend('twig', function ($twig, $c) {
            /** @var Security $security */
            $security = Application::Security();
            if($security != null) {
                $mytwig = $security->setTwigFunctions($twig);
            } else {
                $mytwig = $twig;
            }

            return $mytwig;
        });
    }
}