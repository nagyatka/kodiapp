<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 23.
 * Time: 20:35
 */

namespace KodiApp;


use KodiApp\ContentProvider\ContentProvider;
use KodiApp\Exception\HttpAccessDeniedException;
use KodiApp\Exception\HttpException;
use KodiApp\Exception\HttpInternalErrorException;
use KodiApp\Exception\HttpNotFoundException;
use KodiApp\Response\ErrorResponse;
use KodiApp\Router\RouterInterface;
use KodiApp\Security\Security;
use KodiApp\Session\SessionStorage;
use Model\Core\Twig\Twig;
use Monolog\Logger;
use Pimple\Container;

class Application
{

    const ENV_DEVELOPMENT   = "dev";
    const ENV_PRODUCTION    = "prod";

    /**
     * @var Application
     */
    private static $instance = null;

    /**
     * @var Container
     */
    private $pimple;

    /**
     * ["type","timezone","loglevel","display_errors","error_reporting","force_https","controllers_path","controller_namespace"]
     * @var array
     */
    private $environment;

    /**
     * @var ContentProvider[]
     */
    private $contentProviders;

    /**
     * @var RouterInterface
     */
    private $router;


    private $callableErrorHandler;

    /**
     * @return Application
     */
    public static function getInstance() {
        if(Application::$instance == null) {
            Application::$instance = new Application();
        }
        return Application::$instance;
    }

    /**
     * Application constructor.
     */
    private final function __construct()
    {
        $this->pimple = new Container();
        $this->contentProviders = [];
        $this->callableErrorHandler = function (HttpException $e) {
            if($e instanceof HttpNotFoundException) {
                return new ErrorResponse(404);
            }
            elseif ($e instanceof HttpInternalErrorException) {
                $result = "";
                foreach (debug_backtrace() as $line) {
                    $result .= $line."\n";
                }
                return new ErrorResponse(500,$result);
            }
            elseif ($e instanceof HttpAccessDeniedException) {
                return new ErrorResponse(403);
            }
            else {
                return "HTTP: Undefined error";
            }
        };
    }

    /**
     * Alkalmazás indítása
     * @param ApplicationConfiguration $configuration
     * @throws HttpAccessDeniedException
     */
    public function run(ApplicationConfiguration $configuration) {

        try {
            if(!$configuration) {
                throw new HttpInternalErrorException("Missing configuration");
            }

            $configuration->initializeApplication($this);

            //Csekkolni kell, hogy kell-e erőltetni a https-t
            if ($this->environment["force_https"] && $_SERVER['HTTPS'] != "on") {
                $this->redirectHttps();
                return;
            }

            $httpMethod = $_SERVER['REQUEST_METHOD'];
            $uri = $_SERVER['REQUEST_URI'];

            //Jogosultság ellenőrzése, ha van definiálva security
            $security = $this->getSecurity();
            if ($security != null && $security->checkPermissions($uri)) {
                throw new HttpAccessDeniedException();
            }

            //Routeból lekérdezni, hogy mit kell futtatni
            $router = $this->getRouter();
            $routingResult = $router->findRoute($httpMethod, $uri);
            $controllerParts = explode("::", $routingResult["handler"]);
            $controllerName = $controllerParts[0];
            $controllerMethod = $controllerParts[1];

            //Megfelelő controller betöltése
            if (!$this->loadController($controllerName)) {
                throw new HttpInternalErrorException($controllerName . " has been not found!");
            }

            //Futtatás
            $controllerFullName = $this->environment["controller_namespace"] . $controllerName;
            $controller = new $controllerFullName();
            $result = $controller->{$controllerMethod}($routingResult["params"]);

            //Eredmény printelése
            print $result;
            return;

        } catch (HttpException $e) {
            print $this->{$this->callableErrorHandler}($e);
        } catch (\Exception $e) {
            print "Unhandled exception!\n";
            if(Application::isDevelopmentEnv()) print $e->getMessage();
        }

    }

    /**
     * Visszaadja a containert.
     * @return Container
     */
    public function getPimple() {
        return $this->pimple;
    }

    /**
     * @return Security
     */
    public function getSecurity() {
        return $this->pimple["security"];
    }

    /**
     * @return Logger
     */
    public function getLogger() {
        return $this->pimple["logger"];
    }

    /**
     * @return SessionStorage
     */
    public function getSession() {
        return $this->pimple["session"];
    }

    /**
     * @return Twig
     */
    public function getTwig() {
        return $this->pimple["twig"];
    }

    /**
     * @return array
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * @return bool
     */
    public static function isDevelopmentEnv() {
        return Application::getInstance()->getEnvironment()["type"] == Application::ENV_DEVELOPMENT;
    }

    /**
     * @param array $environment
     */
    public function setEnvironment($environment)
    {
        $this->environment = $environment;
        date_default_timezone_set($this->environment["timezone"]);
        ini_set("display_errors", $this->environment["display_errors"]);
        error_reporting($this->environment["error_reporting"]);
    }

    /**
     * @param ContentProvider $contentProvider
     */
    public function addPageContent(ContentProvider $contentProvider) {
        $this->contentProviders[] = $contentProvider;
    }

    /**
     * @return ContentProvider[]
     */
    public function getPageContents() {
        return $this->contentProviders;
    }

    /**
     * @return RouterInterface
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @param RouterInterface $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @param $callable
     */
    public function setErrorHandler($callable) {
        $this->callableErrorHandler = $callable;
    }

    private function loadController($controllerName) {
        $class_path = $this->environment["controller_path"]."/".$controllerName;
        if (file_exists($class_path)) {
            include $class_path;
            return true;
        } else {
            return false;
        }
    }

    private function redirectHttps() {
        $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        header("Location:$redirect");
    }

}