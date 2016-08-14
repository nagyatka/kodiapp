<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 20.
 * Time: 15:31
 */

namespace KodiApp\Security;


use KodiApp\Application;
use Monolog\Logger;
use PandaBase\Connection\ConnectionManager;

class Security
{

    const ACCESS_GRANTED = 0;
    const ACCESS_DENIED  = 1;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $userClassName;

    /**
     * [
     *  [
     *      "path" => "/foo",
     *      "role" => AUTHENTICATED_USER
     *  ],
     *  ...
     * ]
     * @var array
     */
    private $permissions;

    /**
     * Security constructor.
     * @param Logger $logger
     * @param array $configuration
     */
    public function __construct($logger,$configuration)
    {
        $this->logger = $logger;
        $this->userClassName = $configuration["user_class"];
        $this->permissions = $configuration["permissions"];
    }

    /**
     *
     * @param UserInterface $user
     * @return string
     * @throws InvalidPasswordException
     * @throws UserNotExists
     */
    public function loginCheck(UserInterface $user)
    {
        // Kísérlet logolása
        $this->logger->addInfo('Login attempt',[
            'username'      => $user->getUsername()
        ]);


        // Ha nem létezik a username, akkor hibát dobunk.
        if(!$user->isValidUsername()) {
            $this->logger->addInfo('Login failed',[
                'reason'    => 'wrong username',
                'username'  => $user->getUsername()
            ]);
            throw new UserNotExists($user->getUsername());
        }

        if($this->isValidPassword($user)) {
            $session = Application::Session();
            //Session változók beállítása
            $session->set("loggedin",true);
            $session->set("updated",time());
            $session->set("username",$user->getUsername());
            $session->set("userid",$user->getUserId());
            $session->set("token",$this->generateToken());
            $this->logger->addInfo('Login success',[
                'username'  => $user->getUsername(),
                'userid'    => $user->getUserId()
            ]);
            return [
                "userid"        => $user->getUserId(),
                "session_token" => $session->get("token")
            ];
        } else {
            throw new InvalidPasswordException();
        }
    }

    /**
     * Felhasználó kiléptetése.
     */
    public function logout() {
        session_unset();
        session_destroy();
    }

    /**
     * Eldönti, hogy a paraméterben megadott userhez megadott jelszó egyezik-e azzal, amit az adatbázis
     * @param UserInterface $user
     * @return bool
     */
    private function isValidPassword(UserInterface $user) {
        $salt = substr($user->getHashedPassword(), 0, 64); // 64 karakter a salt, másik 64 a hash hex-ben
        $hash = $this->hashPassword($user->getRawPassword(), $salt);
        if ($hash->output == $user->getHashedPassword()) return true;
        else return false;
    }

    /**
     * Jelszó generáló algoritmus.
     * @param $algorithm
     * @param $password
     * @param $salt
     * @param $count
     * @param $key_length
     * @param bool $raw_output
     * @return mixed|string
     */
    private function pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output = false)
    {
        $algorithm = strtolower($algorithm);
        if(!in_array($algorithm, hash_algos(), true))
            trigger_error('PBKDF2 ERROR: Invalid hash algorithm.', E_USER_ERROR);
        if($count <= 0 || $key_length <= 0)
            trigger_error('PBKDF2 ERROR: Invalid parameters.', E_USER_ERROR);

        if (function_exists("hash_pbkdf2")) {
            // The output length is in NIBBLES (4-bits) if $raw_output is false!
            if (!$raw_output) {
                $key_length = $key_length * 2;
            }
            return hash_pbkdf2($algorithm, $password, $salt, $count, $key_length, $raw_output);
        }

        $hash_length = strlen(hash($algorithm, "", true));
        $block_count = ceil($key_length / $hash_length);

        $output = "";
        for($i = 1; $i <= $block_count; $i++) {
            // $i encoded as 4 bytes, big endian.
            $last = $salt . pack("N", $i);
            // first iteration
            $last = $xorsum = hash_hmac($algorithm, $last, $password, true);
            // perform the other $count - 1 iterations
            for ($j = 1; $j < $count; $j++) {
                $xorsum ^= ($last = hash_hmac($algorithm, $last, $password, true));
            }
            $output .= $xorsum;
        }

        if($raw_output)
            return substr($output, 0, $key_length);
        else
            return bin2hex(substr($output, 0, $key_length));
    }

    /**
     * Hash generálása.
     * @param string $pw
     * @param string|bool $salt
     * @return \stdClass
     */
    public function hashPassword($pw, $salt=false) {
        // Salt generálás
        if (!$salt) {
            $salt = bin2hex(openssl_random_pseudo_bytes(32));
        }
        $hash = $this->pbkdf2("sha256", $pw, $salt, 10000, 32);
        $r = new \stdClass();
        $r->output = $salt.$hash;
        $r->salt = $salt;
        $r->hash = $hash;
        return $r;
    }

    /**
     * 16 bájtos token generálása.
     * @return string
     */
    public function generateToken() {
        return bin2hex(openssl_random_pseudo_bytes(16));
    }

    /**
     * Megvizsgálja, hogy a paraméterben megadott jelszó elég erős-e, megfelel-e a $settings paraméterben megadott
     * feltételeknek. Visszatérési értéke igaz, ha megfelelő a jelszó, amúgy hamis.
     *
     * Használható feltételek:
     *  - length: Minimum hány karakterből álljon
     *  - uppers: Minimum hány nagybetűt tartalmazzon
     *  - lowers: Minimum hány kisbetűt tartalmazzon
     *  - digits: Minimum hány számjegyet tartalmazzon
     *
     * Ezeknek a paramétereknek megadása tetszőleges, de erősen ajánlott. Kihagyásuk esetén nem végez ellenőrzést.
     *
     * Példa:
     *
     *  $security->checkPasswordStrength($password,[
     *      "length"    => 6,
     *      "uppers"    => 1,
     *      "lowers"    => 1,
     *      "digits"    => 1
     * ]);
     *
     *
     * @param string $password
     * @param array $settings
     * @return bool
     */
    public function checkPasswordStrength($password,$settings = []) {
        // count how many lowercase, uppercase, and digits are in the password
        $uc = 0; $lc = 0; $num = 0; $other = 0;
        for ($i = 0, $j = strlen($password); $i < $j; $i++) {
            $c = substr($password,$i,1);
            if (preg_match('/^[[:upper:]]$/',$c)) {
                $uc++;
            } elseif (preg_match('/^[[:lower:]]$/',$c)) {
                $lc++;
            } elseif (preg_match('/^[[:digit:]]$/',$c)) {
                $num++;
            } else {
                $other++;
            }
        }
        $length = $uc + $lc + $num + $other;

        if(isset($settings["length"]) && ($length < intval($settings["length"]))) {
            return false;
        }
        if(isset($settings["uppers"]) && ($uc < intval($settings["uppers"]))) {
            return false;
        }
        if(isset($settings["lowers"]) && ($lc < intval($settings["lowers"]))) {
            return false;
        }
        if(isset($settings["digits"]) && ($num < intval($settings["digits"]))) {
            return false;
        }

        return true;
    }

    /**
     * @return UserInterface
     */
    public function getUser() {
        $session = Application::Session();
        if(!$session->get("loggedin")) {
            return null;
        } else {
            $class_name = $this->userClassName;
            /** @var UserInterface $user */
            $user = new $class_name($session->get("userid"));
            return $user;
        }
    }

    /**
     * @return bool
     */
    public function checkPermissions($uri) {
        foreach ($this->permissions as $permissionPath => $permissionRoles) {
            if($permissionPath[0] !== "/") {
                $permissionPath = "/".$permissionPath;
            }
            if(substr($permissionPath,-1) !== "/") {
                $permissionPath = $permissionPath."/";
            }
            $match = preg_match($permissionPath,$uri);
            if ($match == 1) {
                if(in_array(Role::ROLE_ANONYMOUS,$permissionRoles)) {
                    ConnectionManager::getInstance()->registerAccessUser($this->getUser());
                    return true;
                }
                $user = $this->getUser();
                if($user == null) {
                    return false;
                }
                foreach ($permissionRoles as $permissionRole) {
                    if(in_array($permissionRole,$user->getRoles())) {
                        ConnectionManager::getInstance()->registerAccessUser($user);
                        return true;
                    }
                }
                return false;
            }
        }
        return true;
    }
}