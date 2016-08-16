# KodiApp

PHP application framework

## Install

```bash
$ composer require nagyatka/kodiapp
```

## Documentation v1.0
**Magyar nyelvű dokumentáció**

### Alkalmazás indítása
index.php
```php
define('PATH_BASE', dirname(__FILE__).'/..' );

// Composer autoloader
require '../vendor/autoload.php';

use KodiApp\Application;
use MyApp\MyAppConfiguration;

Application::getInstance()->run(new MyAppConfiguration());
```

Egy alkalmazást az `Application::getInstance()` osztály metódus meghívásával lehet elérni. Mivel az Application 
Singleton mintát valósít meg, emiatt az alkalmazás futása során bárhol elérhető ezzel a metódus hívással.

Az alkalmazást elindítani a run metódussal lehet, amely paraméterként egy olyan osztály vár, ami megvalósítja az 
`ApplicationConfiguration` interfészt. Itt csak a `initializeApplication` metódust kell implementálni, amelyet az
alkalmazás futása elején fog majd meghívni. Itt kell regisztrálni az adatbázis kapcsolatot, routokat, biztonsági 
beállításokat, stb.

Ez a megközelítés nagy fokú szabadságot biztosít, hiszen megvalósíthatjuk úgy a konfigurációt, hogy konfigurációs osztályon
belül töltjük be a konfigurációs fájlokat, de az is megoldható, hogy konstruktoron keresztül oldjuk meg ugyanezt.

```php
class MyAppConfiguration implements ApplicationConfiguration
{
    public function __construct()
    {
        // ...
    }

    public function initializeApplication(Application $application)
    {
        // ...
    }
}
```

### Komponensek

A különböző komponenseket az `ApplicationConfiguration` interfészt megvalóstó osztály `initializeApplication` metódusában
kell inicializálni.

#### Routolás

Az útvonalakat egy tömbben kell definiálni a következő struktúra szerint:
```php
Struktúra:    
    [route_name] => [
        "method"    =>  [HTTP method],
        "url"       =>  [url],
        "handler"   =>  "[controller_class]::[controller_class_function]",
    ], ...
    
Példa:
    "home" => [
        "method"    =>  "GET",
        "url"       =>  "/",
        "handler"   =>  "HomeController::handleIndex",
    ],
```

Router inicializálása:
```php
class MyAppConfiguration implements ApplicationConfiguration
{
    private $myroutes = [
         "home" => [
                "method"    =>  "GET",
                "url"       =>  "/",
                "handler"   =>  "HomeController::handleIndex",
         ],  
    ];
    
    public function initializeApplication(Application $application)
    {
        $router = new SimpleRouter();
        $router->setRoutes($this->myroutes);
        $application->setRouter($router);
    }
}
```

A route-hoz tartozó HomeController:
```php
class HomeController 
{
    public function handleIndex()
    {
        return "Hello world!";
    }
}
```

Paraméterek beállítása az urlben:
```php
class MyAppConfiguration implements ApplicationConfiguration
{
    private $myroutes = [
         "home" => [
                "method"    =>  "GET",
                "url"       =>  "/user/{user_id:[0-9]+}",
                "handler"   =>  "UserController::getUser",
         ],  
    ];
    
    public function initializeApplication(Application $application)
    {
        $router = new SimpleRouter();
        $router->setRoutes($this->myroutes);
        $application->setRouter($router);
    }
}

class UserController 
{
    public function getUser($params)
    {
        return "User_id: ".$params["user_id"];
    }
}
```
A megadott url-ekben tetszőlegesen beállíthatóak paraméterek, amiket a metódus egy asszociatív tömbben kap meg. 
Az url paramétereknél `:`-tal elválasztva megadható reguláris kifejezés is, amelynek teljesülnie kell, hogy meghívódjon
a handlerben megadott metódus!














