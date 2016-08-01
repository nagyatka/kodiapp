<?php
use KodiApp\Session\Session;

/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 31.
 * Time: 22:46
 */
class SessionTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var \KodiApp\Session\SessionStorage
     */
    private $sessionStorage;

    protected function setUp()
    {
        \PandaBase\Connection\ConnectionManager::getInstance()->initializeConnection([
            "name"      =>  "database_connection", //Panabasen belüli azonosító
            "driver"    =>  "mysql",
            "dbname"    =>  "kodiapp",
            "host"      =>  "localhost",
            "user"      =>  "root",
            "password"  =>  "",
            "charset"   =>  "utf8",
        ]);

        Session::initSession([
            "name"      => "session", // A név megegyezik az adatbázis tábla nevével
            "lifetime"  => 7200
        ]);
        $this->sessionStorage = new \KodiApp\Session\SessionStorage();
    }


    public function testGetEmpty() {
        assertEquals(NULL,$this->sessionStorage->get("val"));
    }


}