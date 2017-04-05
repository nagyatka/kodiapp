<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 20.
 * Time: 15:40
 */

namespace KodiApp\ServiceProvider;


use PandaBase\Connection\ConnectionManager;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

class DatabaseProvider implements ServiceProviderInterface
{
    private $databaseConfig;

    /**
     * DatabaseProvider constructor.
     * @param array $config
     */
    public function __construct($config)
    {
        $this->databaseConfig = $config;
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
        $configuration = $this->databaseConfig;
        if($this->isAssociativeConfiguration($configuration)) {
            ConnectionManager::getInstance()->initializeConnection($configuration);
            if(strtolower($configuration["charset"]) === "utf8") {
                if($configuration["driver"] == "mysql") {
                    ConnectionManager::getInstance()->getConnection($configuration["name"])->setNamesUTF8();
                }
            }
        } else {
            ConnectionManager::getInstance()->initializeConnections($configuration);
            foreach ($configuration as $config) {
                if(strtolower($config["charset"]) === "utf8") {
                    if($config["driver"] == "mysql") {
                        ConnectionManager::getInstance()->getConnection($config["name"])->setNamesUTF8();
                    }
                }
            }
        }

        /*
         * A ConnectionManager önmagában is globálisan tudja tárolni a saját állapotát, ezért inkább factory-val kell
         * használni, nehogy a pimple-be beragadjon hibásan valami.
         */
        $pimple['db'] = $pimple->factory(function ($c) {
            return ConnectionManager::getInstance();
        });
    }

    private function isAssociativeConfiguration(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

}