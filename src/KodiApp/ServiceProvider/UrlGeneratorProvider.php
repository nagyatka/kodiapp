<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 12.
 * Time: 15:26
 */

namespace KodiApp\ServiceProvider;


use KodiApp\Application;
use KodiApp\Router\UrlGenerator;
use KodiApp\Twig\Twig;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class UrlGeneratorProvider implements ServiceProviderInterface
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
        $pimple['url_generator'] = function ($c) {
            return new UrlGenerator(Application::Router());
        };

        //url_generate függvény hozzáadása a twighez
        $pimple->extend('twig', function ($twig, $c) {
            /** @var Twig $mytwig */
            $mytwig = $twig;
            /** @var UrlGenerator $urlGenerator */
            $urlGenerator = $c["url_generator"];
            $url_generator = new \Twig_SimpleFunction("url_generate",function($url_name,$parameters) use($urlGenerator) {
                return $urlGenerator->generate($url_name,$parameters);
            });
            $mytwig->getTwigEnvironment()->addFunction($url_generator);
            return $mytwig;
        });
    }
}















