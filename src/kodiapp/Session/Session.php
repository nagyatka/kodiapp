<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 21.
 * Time: 15:30
 */

namespace KodiApp\Session;


use PandaBase\Connection\ConnectionManager;
use PandaBase\Connection\TableDescriptor;
use PandaBase\Record\SimpleRecord;

/**
 * Class Session
 *
 * Session-t reprezentáló osztály. Az adatokat adatbázisban tárolja, a kapcsolatot a PandaBase ORM segítségével tartja.
 * Az adatbázis tábla neve tetszőleges, de meg fog egyezni a session nevével.
 *
 * A következő mezőket használja (és egyben ezek kötelezőek is!):
 *      - id (session azonosító)
 *      - data (adat)
 *      - remote_ip (IP cím)
 *      - updated (datetime)
 *
 *
 * @package KodiApp\Session
 */
class Session extends SimpleRecord
{

    /**
     * @var string
     */
    private static $sessionName;

    /**
     * @var string
     */
    private static $sessionLifetime;

    /**
     * Session constructor.
     * @param int $id
     * @param null $values
     */
    function __construct($id, $values = null)
    {
        $tableDescriptor = new TableDescriptor([
            TABLE_NAME  =>  self::$sessionName,
            TABLE_ID    =>  "id",
        ]);
        parent::__construct($tableDescriptor,$id,$values);
    }



    /**
     * @param array $settings
     * @return bool
     */
    public static function initSession($settings) {

        self::$sessionName = $settings["name"];
        self::$sessionLifetime = $settings["lifetime"];

        session_set_save_handler(
            "KodiApp\Session\Session::Open",
            "KodiApp\Session\Session::Close",
            "KodiApp\Session\Session::Read",
            "KodiApp\Session\Session::Write",
            "KodiApp\Session\Session::Destroy",
            "KodiApp\Session\Session::garbageCollect"
        );

        //Ha HTTPs van beállítva, akkor secure-re állítjuk a session-t.
        session_set_cookie_params(0,'/',null, (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']=="on"), true);
        session_name(self::$sessionName);
        ini_set("session.gc_maxlifetime", self::$sessionLifetime);
        ini_set("session.gc_divisor", "100");
        ini_set("session.gc_probability", "1");

        session_start();
        return true;
    }

    public static function Open($path, $session_name) {
        return true;
    }

    public static function Close() {
        return true;
    }

    public static function Read($sid) {
        $session = new Session($sid);
        if($session->isValid()) {
            return $session->getAll();
        } else {
            return false;
        }
    }

    public static function Write($sid, $data) {
        $session = new Session($sid);

        if($session->isValid()) {
            $session["data"]    = $data;
            $session["updated"] = date('Y-m-d H:i:s');
            ConnectionManager::getInstance()->persist($session);
        } else {
            $newSession = new Session(CREATE_INSTANCE,[
                "id"        =>  $sid,
                "data"      =>  $data,
                "remote_ip" => Session::getip(),
                "updated"   => date('Y-m-d H:i:s')
            ]);
            ConnectionManager::getInstance()->persist($newSession);
        }
        return true;
    }

    public static function Destroy($sid) {
        if (strlen($sid) > 0) {
            $statement = ConnectionManager::getInstance()->getDefault()->prepare("DELETE FROM ".self::$sessionName." WHERE id=:id");
            $statement->bindValue("id",$sid);
            $statement->execute();
        }
        return true;
    }

    public static function garbageCollect($max_life_sec) {
        $max_life_min=round($max_life_sec/60);

        $statement = ConnectionManager::getInstance()->getDefault()->prepare("
          DELETE from ".self::$sessionName." WHERE updated<date_sub(NOW(), interval :max_life_min minute)
        ");
        $statement->bindValue("max_life_min",$max_life_min);
        $statement->execute();
        return true;
    }

    private static function validip($ip) {
        if (!empty($ip) && ip2long($ip)!=-1) {
            $reserved_ips = array (
                array('0.0.0.0','2.255.255.255'),
                array('10.0.0.0','10.255.255.255'),
                array('127.0.0.0','127.255.255.255'),
                array('169.254.0.0','169.254.255.255'),
                array('172.16.0.0','172.31.255.255'),
                array('192.0.2.0','192.0.2.255'),
                array('192.168.0.0','192.168.255.255'),
                array('255.255.255.0','255.255.255.255')
            );
            foreach ($reserved_ips as $r) {
                $min = ip2long($r[0]);
                $max = ip2long($r[1]);
                if ((ip2long($ip) >= $min) && (ip2long($ip) <= $max)) return false;
            }
            return true;
        } else {
            return false;
        }
    }

    private static function getip() {
        if (Session::validip($_SERVER["HTTP_CLIENT_IP"])) {
            return $_SERVER["HTTP_CLIENT_IP"];
        }
        foreach (explode(",",$_SERVER["HTTP_X_FORWARDED_FOR"]) as $ip) {
            if (Session::validip(trim($ip))) {
                return $ip;
            }
        }
        if (Session::validip($_SERVER["HTTP_X_FORWARDED"])) {
            return $_SERVER["HTTP_X_FORWARDED"];
        } elseif (Session::validip($_SERVER["HTTP_FORWARDED_FOR"])) {
            return $_SERVER["HTTP_FORWARDED_FOR"];
        } elseif (Session::validip($_SERVER["HTTP_FORWARDED"])) {
            return $_SERVER["HTTP_FORWARDED"];
        } elseif (Session::validip($_SERVER["HTTP_X_FORWARDED"])) {
            return $_SERVER["HTTP_X_FORWARDED"];
        } else {
            return $_SERVER["REMOTE_ADDR"];
        }
    }
}