<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 21.
 * Time: 16:48
 */

namespace KodiApp\ServiceProvider;

use KodiApp\Session\PdoSessionHandler;
use KodiApp\Session\Session;
use KodiApp\Session\SessionStorage;
use PandaBase\Connection\ConnectionManager;
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
        /** @var ConnectionManager $db */
        $db = $pimple["db"];

        if (!isset($configuration['name'])) {
            throw new \InvalidArgumentException('You must provide the "name" option for a PdoSessionStorage.');
        }
        if (!isset($configuration['lifetime'])) {
            throw new \InvalidArgumentException('You must provide the "lifetime" option for a PdoSessionStorage.');
        }

        $sessionHandler = new PdoSessionHandler($db->getDefault()->getDatabase(),[
            "db_table" => $configuration["name"]
        ]);
        session_set_save_handler($sessionHandler);
        //Ha HTTPs van beállítva, akkor secure-re állítjuk a session-t.
        session_set_cookie_params(0,'/',null, (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=="on"), true);
        session_name($configuration["name"]);
        ini_set("session.gc_maxlifetime", $configuration["lifetime"]);
        ini_set("session.gc_divisor", "100");
        ini_set("session.gc_probability", "1");

        session_start();

        $pimple['session'] = function ($c) {
            return new SessionStorage();
        };
    }
}