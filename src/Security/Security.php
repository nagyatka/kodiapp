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
            $session = Application::getInstance()->getSession();
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
        if ($hash->output == $user->getRawPassword()) return true;
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
     * Megvizsgálja, hogy a paraméterben megadott jelszó elég erős-e. Visszatérési értéke igaz, ha megfelelő a jelszó,
     * amúgy hamis.
     *
     * @param string $password
     * @return bool
     */
    public function checkPasswordStrength($password) {
        //TODO
        return true;
    }

    /**
     * @return UserInterface
     */
    public function getUser() {
        $session = Application::getInstance()->getSession();
        if(!$session->get("loggedin")) {
            return null;
        } else {
            $class_name = $this->userClassName;
            return new $class_name($session->get("userid"));
        }
    }

    /**
     * @return bool
     */
    public function checkPermissions($uri) {
        foreach ($this->permissions as $permission) {
            $pos = strpos($uri,$permission["path"]);
            if ($pos != false) {
                $user = $this->getUser();
                if($user == null) {
                    return false;
                }
                if(in_array($permission["role"],$user->getRoles())) {
                    return true;
                } else {
                    return false;
                }
            }
        }
        return true;
    }
}