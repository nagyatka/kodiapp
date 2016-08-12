<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 20.
 * Time: 15:27
 */

namespace KodiApp\ServiceProvider;


use KodiApp\Security\Security;
use KodiApp\Twig\Twig;
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

        //Jogosultság kezelése twighez
        $pimple->extend('twig', function ($twig, $c) {
            /** @var Twig $mytwig */
            $mytwig = $twig;
            /** @var Security $security */
            $security = $c["security"];

            // Jogosultság function
            $is_granted = new \Twig_SimpleFunction('is_granted', function ($roles) use($security) {

                if(is_array($roles)) {
                    foreach ($roles as $role) {
                        if($security->getUser()->hasRole($role)) {
                            return true;
                        }
                    }
                    return false;
                } else {
                    return $security->getUser()->hasRole($roles);
                }
            });
            $mytwig->getTwigEnvironment()->addFunction($is_granted);

            //User_id
            $get_user_id = new \Twig_SimpleFunction('get_user_id', function () use($security) {
                return $security->getUser()->getUserId();
            });
            $mytwig->getTwigEnvironment()->addFunction($get_user_id);

            //Username
            $get_username = new \Twig_SimpleFunction('get_username', function () use($security) {
                return $security->getUser()->getUsername();
            });
            $mytwig->getTwigEnvironment()->addFunction($get_username);

            return $mytwig;
        });
    }
}