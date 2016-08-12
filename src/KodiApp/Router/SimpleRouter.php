<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 24.
 * Time: 14:14
 */

namespace KodiApp\Router;


use FastRoute\Dispatcher\GroupCountBased;
use FastRoute\RouteCollector;
use function FastRoute\simpleDispatcher;
use KodiApp\Application;
use KodiApp\Exception\HttpNotFoundException;

class SimpleRouter implements RouterInterface
{
    /**
     * @var GroupCountBased
     */
    private $dispatcher;

    /**
     * @var array
     */
    private $routes;

    /**
     * Paraméterek betöltése.
     *
     * A tömb struktúra:
     * [
     *      [
     *          "method"    =>  [POST|GET|PUT|DELETE],
     *          "url"       =>  "/foo/bar/1222",
     *          "handler"   =>  ClassName::methodName
     *      ],
     *      ....
     * ]
     *
     * @param array $routes
     */
    public function setRoutes(array $routes)
    {
        $this->routes = $routes;
        $this->dispatcher = simpleDispatcher(function(RouteCollector $r) use ($routes) {
            foreach ($routes as $route) {
                $r->addRoute($route["method"],$route["url"],$route["handler"]);
            }

            //Nyelvi url betöltése, ha van
            $translator = Application::Translator();
            if($translator != null && ($url = $translator->getCookieSetUrl()) != null) {
                $r->addRoute($url["method"],$url["url"],$url["handler"]);
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
                return [
                    "handler"   =>  $routeInfo[1],
                    "params"    =>  $routeInfo[2]
                ];
            default:
                return [];
        }
    }
}