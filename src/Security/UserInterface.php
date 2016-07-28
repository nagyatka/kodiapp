<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 20.
 * Time: 15:35
 */

namespace KodiApp\Security;

/**
 * Interface UserInterface
 * Az interfész szerepe, hogy a Security osztály egy egységes felületen keresztül tudja elérni a felhasználókat, függetlenül
 * attól, hogy egy projektnél milyen elvárások vannak egy User osztállyal szemben.
 *
 * @package Model\Core\Security
 */
interface UserInterface
{

    /**
     * Visszadja a jelszót, amit egy bejelentkezési kísérletnél adott meg a felhasználó.
     * @return string
     */
    public function getRawPassword();

    /**
     * Visszadja azt a jelszót, amit hashelve tárolunk az adatbázisban az adott userhez.
     * @return string
     */
    public function getHashedPassword();

    /**
     * Kitörli a bejelentkezési kísérletnél megadott jelszót.
     */
    public function clearRawPassword();

    /**
     * Visszaadja a beállított felhasználó nevet.
     * @return string
     */
    public function getUsername();

    /**
     * Megmondja, hogy érvényes-e a felhasználónév
     * @return bool
     */
    public function isValidUsername();

    /**
     * @return int
     */
    public function getUserId();

    /**
     * @return array
     */
    public function getRoles();
}