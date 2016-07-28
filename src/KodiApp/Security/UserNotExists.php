<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 20.
 * Time: 16:19
 */

namespace KodiApp\Security;


class UserNotExists extends \Exception
{
    /**
     * @var string
     */
    private $username;

    /**
     * UserNotExists constructor.
     * @param string $username
     */
    public function __construct($username)
    {
        $this->username = $username;
        parent::__construct($username." does not exist.");
    }

    /**
     * @return string
     */
    public function getUsername()
    {
        return $this->username;
    }

}