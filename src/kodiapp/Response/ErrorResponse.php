<?php
use KodiApp\Application;

/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 23.
 * Time: 20:32
 */
class ErrorResponse
{
    /**
     * @var int
     */
    private $code;

    /**
     * @var array
     */
    private $content;

    /**
     * ErrorResponse constructor.
     * @param int $code
     * @param array $content
     */
    public function __construct($code,$content = [])
    {
        $this->code = $code;
        $this->content = $content;
    }

    /**
     * The __toString method allows a class to decide how it will react when it is converted to a string.
     *
     * @return string
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring
     */
    function __toString()
    {
        try {
            switch ($this->code) {
                case 404:
                    return Application::getInstance()->getTwig()->render('error/error_404.twig',$this->content);
                default:
                    return Application::getInstance()->getTwig()->render('error/error_404.twig',$this->content);
            }
        } catch(\Exception $e) {
            return Application::getInstance()->getEnvironment() == Application::ENV_DEVELOPMENT ? $e->getMessage() : "";
        }
    }
}