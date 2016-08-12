<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 12.
 * Time: 12:12
 */

namespace KodiApp\Router;


use KodiApp\Application;
use KodiApp\Translator\Translator;

class UrlGenerator
{

    /**
     * UrlGenerator constructor.
     */
    public function __construct()
    {
    }

    public function generate($url_name,$parameters) {
        $routes = Application::Router()->getRoutes();
        $translator = Application::Translator();
        if(!isset($routes[$url_name])) {
            return $url_name;
        }

        // Ha szükséges be kell rakni a nyelvet is a paraméterek közé
        if (
            $translator != null &&
            !$translator->getStrategy()!= Translator::STRATEGY_ONLY_COOKIE &&
            !$routes[$url_name]["locale"] == LanguageRouter::LOCALE_NOT_ALLOWED
        ) {
            $parameters["locale"] = $translator->getLocale();
        }

        $url = $routes[$url_name]["url"];
        $resultUrl = "";
        $index = 0;
        while(($firstPos = strpos($url,"{",$index)) != false) {
            $lastPos = strpos($url,"}",$firstPos);
            $name = explode(":",substr($url,$firstPos+1,($lastPos-$firstPos-1)))[0];
            $resultUrl .= substr($url,$index,$firstPos-$index).(isset($parameters[$name])?$parameters[$name]:"");
            $index = $lastPos+1;
        };
        return $resultUrl;

    }
}





















