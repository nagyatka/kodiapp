<?php
use KodiApp\ContentProvider\ServerContentProvider;
use KodiApp\Exception\HttpAccessDeniedException;
use KodiApp\Exception\HttpInternalErrorException;
use KodiApp\Exception\HttpNotFoundException;
use KodiApp\ServiceProvider\DatabaseProvider;
use KodiApp\ServiceProvider\MonologProvider;
use KodiApp\ServiceProvider\SecurityProvider;
use KodiApp\ServiceProvider\SessionProvider;
use KodiApp\ServiceProvider\TwigProvider;
use Monolog\Logger;

/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 24.
 * Time: 22:19
 */
class TestApplication implements \KodiApp\ApplicationConfiguration
{

    /**
     * @param \KodiApp\Application $application
     */
    public function initializeApplication(\KodiApp\Application $application)
    {
        $router = new \KodiApp\Router\SimpleRouter();
        $router->setRoutes([
            [
              "method"    =>  "GET",
              "url"       =>  "/foo/bar/1222",
              "handler"   =>  "FooController::methodName"
            ],
        ]);
        $application->setRouter(new \KodiApp\Router\SimpleRouter());

        $pimple = $application->getPimple();

        $application->setEnvironment([
            "type"  => \KodiApp\Application::ENV_DEVELOPMENT,
            "timezone" => "Europe/Budapest",
            "loglevel"  =>  Logger::INFO,
            "display_errors" => 1,
            "error_reporting" => E_ERROR | E_WARNING | E_PARSE,
            "force_https"   => false,
            "controllers_path" => "src/Controller",
            "controller_namespace" => "TestApp\\Controller"
        ]);

        // Adatbázis inicializálása
        $pimple->register(new DatabaseProvider([
            "name"      =>  "database_connection", //Panabasen belüli azonosító
            "driver"    =>  "mysql",
            "dbname"    =>  "db_database",
            "host"      =>  "localhost",
            "user"      =>  "root",
            "password"  =>  "",
            "charset"   =>  "utf8",
        ]));

        // Monolog inicializálása
        $pimple->register(new MonologProvider([
            "name"      =>  'test_proj',
            "path"      =>  '/log/admin.log',
            "log_level" =>  Logger::INFO
        ]));

        // Security inicializálása
        $pimple->register(new SecurityProvider([
            "user_class" => TestUser::class,
            "permissions"=> [
                "/myprofile"    =>  \KodiApp\Security\Role::ROLE_USER
            ]
        ]));

        // Session inicializálása
        $pimple->register(new SessionProvider([
            "name"      => "mv_session", // A név megegyezik az adatbázis tábla nevével
            "lifetime"  => 7200
        ]));

        $pimple->register(new TwigProvider([
            "path"                      =>  '/view',
            "page_frame_template_path"  =>  '/page_frame/frame.twig'
        ]));

        $this->addContentProviders($application);


        $application->setErrorHandler(function($error) {
            if($error instanceof HttpNotFoundException) {
                return "HTTP 404: [".$_SERVER['REQUEST_METHOD']."] ".$_SERVER['REQUEST_URI'];
            }
            elseif ($error instanceof HttpInternalErrorException) {
                $result = "";
                foreach (debug_backtrace() as $line) {
                    $result .= $line."\n";
                }
                return "HTTP 500: \n".$result;
            }
            elseif ($error instanceof HttpAccessDeniedException) {
                return "HTTP 403: [".$_SERVER['REQUEST_METHOD']."] ".$_SERVER['REQUEST_URI'];
            }
            else {
                return "HTTP: Undefined error";
            }
        });
    }

    private function addContentProviders(\KodiApp\Application $application) {

        //Szerver információk betöltése
        $application->addPageContent(new ServerContentProvider());
    }
}