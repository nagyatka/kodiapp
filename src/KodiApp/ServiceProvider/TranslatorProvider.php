<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 11.
 * Time: 12:02
 */

namespace KodiApp\ServiceProvider;


use KodiApp\Translator\Translator;
use KodiApp\Twig\Twig;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class TranslatorProvider implements ServiceProviderInterface
{

    /**
     * @var array
     */
    private $configuration;

    /**
     * SecurityProvider constructor.
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        $this->configuration = $configuration;

        if(!isset($configuration["fallbackLocales"]) || !is_array($configuration["fallbackLocales"])) {
            throw new \InvalidArgumentException("You must provide fallbackLocales in Translator configuration.");
        }
        elseif (!isset($configuration["loader"]) || !is_array($configuration["loader"])) {
            throw new \InvalidArgumentException("You must provide loader in Translator configuration.");
        }
        elseif (!isset($configuration["resources"]) || !is_array($configuration["resources"])) {
            throw new \InvalidArgumentException("You must provide resources in Translator configuration.");
        }
        elseif (!isset($configuration["strategy"])) {
            throw new \InvalidArgumentException("You must provide strategy in Translator configuration.");
        }
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
        $pimple['translator'] = function ($c) use($config) {
            $translator = new Translator($config);
            return $translator;
        };

        /*
         * Translate függvény és a getLocale függvény biztosítása a twighez
         */
        $pimple->extend('twig', function ($twig, $c) {
            /** @var Twig $mytwig */
            $mytwig = $twig;
            /** @var Translator $translator */
            $translator = $c["translator"];
            // Translate függvény biztosítása
            $translate = new \Twig_SimpleFunction('translate',function($message,$params = [],$domain = null,$locale = null) use ($translator){
                return $translator->trans($message,$params,$domain,$locale);
            });
            $mytwig->getTwigEnvironment()->addFunction($translate);

            // Aktuális locale biztosítása
            $get_locale = new \Twig_SimpleFunction('get_locale',function() use ($translator){
                return $translator->getLocale();
            });
            $mytwig->getTwigEnvironment()->addFunction($get_locale);

            // Aktuális locale-ok biztosítása
            $get_locales = new \Twig_SimpleFunction('get_locales',function() use ($translator){
                return $translator->getFallbackLocales();
            });
            $mytwig->getTwigEnvironment()->addFunction($get_locales);

            return $mytwig;
        });
    }
}