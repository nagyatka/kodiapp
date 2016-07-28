<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 27.
 * Time: 8:31
 */

namespace KodiApp\ServiceProvider;


use KodiApp\Mailer\Mailer;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class MailerProvider implements ServiceProviderInterface
{
    /**
     * @var array
     */
    private $configuration;

    /**
     * MailerProvider constructor.
     * @param array $configuration
     */
    public function __construct($configuration)
    {

        $this->configuration = $configuration;
    }

    public function register(Container $pimple)
    {
        $configuration = $this->configuration;
        $pimple['mailer'] = function ($c) use($configuration) {
            return new Mailer($configuration);
        };
    }
}