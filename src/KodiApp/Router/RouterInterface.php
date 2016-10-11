<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 24.
 * Time: 13:51
 */

namespace KodiApp\Router;


interface RouterInterface
{
    const NOT_FOUND = 0;
    const FOUND = 1;
    const METHOD_NOT_ALLOWED = 2;

    /**
     * Route-ok átadása.
     *
     * @param array $routes
     */
    public function setRoutes(array $routes);

    /**
     * Visszaadja az összes tárolt route-ot.
     *
     * @return array
     */
    public function getRoutes();

    /**
     * Válasz tömbnek a következő elemekből kell állnia.
     *
     * $result = [
     *  "handler"   =>  ClassName::methodName, (pl: UserController::login)
     *  "params"    =>  [], (azok a paraméterek, amik az urlből jöhetnek)
     * ]
     *
     * @param $method
     * @param $uri
     * @return array
     */
    public function findRoute($method,$uri);

    /**
     * Visszaadja az aktuális url adatait, amiket megadott a felhasználó a route listában.
     *
     * @return array
     */
    public function getActualRoute();

}