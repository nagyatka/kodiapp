<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 11.
 * Time: 17:26
 */

namespace KodiApp\Router;


use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use KodiApp\Application;
use KodiApp\Exception\HttpNotFoundException;

/**
 * Class LanguageRouter
 *
 * Router osztály, amely automatikusan támogatja a nyelv kezelését az url-ekben.
 *
 * A tömb struktúra:
 * [
 *      [
 *          "method"    =>  [POST|GET|PUT|DELETE],
 *          "url"       =>  "/foo/bar/1222",
 *          "handler"   =>  ClassName::methodName,
 *          "locale"    =>  [LanguageRouter::LOCALE_REQUIRED|LanguageRouter::LOCALE_OPTIONAL|LanguageRouter::LOCALE_NOT_ALLOWED]
 *      ],
 *      ....
 * ]
 *
 * @package KodiApp\Router
 */
class LanguageRouter implements RouterInterface
{
    const LOCALE_REQUIRED   = 0;
    const LOCALE_OPTIONAL   = 1;
    const LOCALE_NOT_ALLOWED= 2;

    /**
     * @var GroupCountBased
     */
    private $dispatcher;

    /**
     * @var array
     */
    private $routes;

    /**
     * Route-ok betöltése. A megfelelő tömb struktúra az osztály leírásában található.
     *
     * @param array $routes
     */
    public function setRoutes(array $routes)
    {
        $this->routes = $routes;
        $this->dispatcher = simpleDispatcher(function(RouteCollector $r) use ($routes) {
            foreach ($routes as $route) {
                // Ha kötelező, akkor csak locale-os url-t készítünk.
                if (!isset($route["locale"]) || $route["locale"] == LanguageRouter::LOCALE_REQUIRED) {
                    $r->addRoute($route["method"],"/{locale:[a-z]+}".$route["url"],$route["handler"]);
                }
                // Ha opcionális, akkor mindkét módon definiáljuk az url-t.
                elseif ($route["locale"] == LanguageRouter::LOCALE_OPTIONAL) {
                    $r->addRoute($route["method"],$route["url"],$route["handler"]);
                    $r->addRoute($route["method"],"/{locale:[a-z]+}".$route["url"],$route["handler"]);
                }
                // Ha nem megengedett akkor csak locale nélküli url-t csinálunk
                elseif ($route["locale"] == LanguageRouter::LOCALE_NOT_ALLOWED) {
                    $r->addRoute($route["method"],$route["url"],$route["handler"]);
                }
            }
        });
    }

    /**
     * @inheritdoc
     */
    public function getRoutes()
    {
        return $this->routes;
    }


    /**
     * Visszaadja a paraméterben megadott adatok alapján, a megfelelő handler-t és az ahhoz tartozó esetleges
     * paramétereket.
     *
     * Ha nem talál egyezést abban az esetben HttpNotFoundException hibát dob.
     *
     *
     * @param $method
     * @param $uri
     * @return array
     * @throws HttpNotFoundException
     */
    public function findRoute($method, $uri)
    {
        // Strip query string (?foo=bar) and decode URI
        if (false !== $pos = strpos($uri, '?')) {
            $uri = substr($uri, 0, $pos);
        }
        $uri = rawurldecode($uri);

        $routeInfo = $this->dispatcher->dispatch($method, $uri);
        switch ($routeInfo[0]) {
            case RouterInterface::NOT_FOUND:
                throw new HttpNotFoundException();
            case RouterInterface::METHOD_NOT_ALLOWED:
                throw new HttpNotFoundException();
            case RouterInterface::FOUND:
                $params = $routeInfo[2];
                //Ha van locale, akkor azt beállítjuk és töröljük a paraméter listából
                if(isset($params["locale"])) {
                    Application::Translator()->setLocale($params["locale"]);
                    unset($params["locale"]);
                }
                return [
                    "handler"   =>  $routeInfo[1],
                    "params"    =>  $params
                ];
            default:
                return [];
        }
    }
}