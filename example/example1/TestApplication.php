<?php
use KodiApp\ContentProvider\ServerContentProvider;
use KodiApp\Exception\HttpAccessDeniedException;
use KodiApp\Exception\HttpInternalErrorException;
use KodiApp\Exception\HttpNotFoundException;
use KodiApp\ServiceProvider\DatabaseProvider;
use KodiApp\ServiceProvider\MonologProvider;
use KodiApp\ServiceProvider\SecurityProvider;
use KodiApp\ServiceProvider\SessionProvider;
use KodiApp\ServiceProvider\TranslatorProvider;
use KodiApp\ServiceProvider\TwigProvider;
use KodiApp\Translator\Translator;
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
            [
                "method"    =>  "GET",
                "url"       =>  "/news/{news_id}",
                "handler"   =>  "NewsController::renderNews"
            ],

        ]);
        $application->setRouter(new \KodiApp\Router\SimpleRouter());

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
        $application->register(new DatabaseProvider([
            "name"      =>  "database_connection", //Pandabasen belüli azonosító
            "driver"    =>  "mysql",
            "dbname"    =>  "db_database",
            "host"      =>  "localhost",
            "user"      =>  "root",
            "password"  =>  "",
            "charset"   =>  "utf8",
        ]));

        // Monolog inicializálása
        $application->register(new MonologProvider([
            "name"      =>  'test_proj',
            "path"      =>  '/log/admin.log',
            "log_level" =>  Logger::INFO
        ]));

        $application->register( new TranslatorProvider([
            "fallbackLocales" => [
                "hu","en"
            ],
            "loader"   => [
                "dev" => Translator::LOADER_ARRAY,
                "prod"=> Translator::LOADER_SERIALIZED
            ],
            "resources" => [
                "hu" => [
                    "/asd/asd",
                ]
            ],
            "strategy"  => Translator::STRATEGY_ONLY_COOKIE,
            //Required at STRATEGY_ONLY_COOKIE
            "cookie_set_url" => "/lang/set/{locale:[a-z]+}"
        ]));

        // Security inicializálása
        $application->register(new SecurityProvider([
            "user_class" => TestUser::class,
            "permissions"=> [
                "/myprofile"    =>  \KodiApp\Security\Role::ROLE_USER
            ]
        ]));

        // Session inicializálása
        $application->register(new SessionProvider([
            "name"      => "mv_session", // A név megegyezik az adatbázis tábla nevével
            "lifetime"  => 7200
        ]));

        $application->register(new TwigProvider([
            "path"                      =>  '/view',
            "page_frame_template_path"  =>  '/page_frame/frame.twig',
            "content_providers"         =>  [
                // Szerver információk
                new ServerContentProvider(),
            ]
        ]));

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
}