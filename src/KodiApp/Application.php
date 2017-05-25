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
use KodiApp\Heartbeat\Heartbeat;
use KodiApp\Heartbeat\HeartbeatListener;
use KodiApp\Response\ErrorResponse;
use KodiApp\Router\RouterInterface;
use KodiApp\Router\UrlGenerator;
use KodiApp\Security\Security;
use KodiApp\Session\SessionStorage;
use KodiApp\Translator\Translator;
use KodiApp\Twig\Twig;
use Monolog\Logger;
use Pimple\Container;
use Pimple\ServiceProviderInterface;

/**
 * Class Application
 *
 * Applikáció osztály. Egy singleton mintát megvalósító osztály, amelyen keresztül hozzáférhető az alkalmazáshoz
 * kapcsolódó összes beállítás és objektum, amiket globálisan el kell érni.
 *
 *
 *
 *
 * @package KodiApp
 */
class Application
{

    const ENV_DEVELOPMENT   = "dev";
    const ENV_PRODUCTION    = "prod";

    /**
     * Singleton minta
     *
     * @var Application
     */
    private static $instance = null;

    /**
     * Pimple container.
     *
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
    protected function __construct()
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
            $security = self::Security();
            if ($security != null && !$security->checkPermissions($uri)) {
                throw new HttpAccessDeniedException();
            }

            //Routeból lekérdezni, hogy mit kell futtatni
            $router = self::Router();
            $routingResult = $router->findRoute($httpMethod, $uri);
            $controllerParts = explode("::", $routingResult["handler"]);
            $controllerName = $controllerParts[0];
            $controllerMethod = $controllerParts[1];

            if($controllerName === "Translator") {
                $translator = Application::Translator();
                $result = $translator->{$controllerMethod}($routingResult["params"]);
            } else {
                //Megfelelő controller betöltése
                if (!$this->loadController($controllerName) && $controllerName!="Translator") {
                    throw new HttpInternalErrorException($controllerName . " has been not found!");
                }

                //Futtatás
                $controllerFullName = $this->environment["controller_namespace"] . $controllerName;
                $controller = new $controllerFullName();
                $result = $controller->{$controllerMethod}($routingResult["params"]);
            }

            //Eredmény printelése
            print $result;
            return;

        } catch (HttpException $e) {
            $handler = $this->callableErrorHandler;
            print $handler($e);
        } catch (\Exception $e) {
            print "Unhandled exception!\n";
            if(Application::isDevelopmentEnv()){
                print $e->getMessage()."\n";
                print str_replace("#","<br>#",$e->getTraceAsString());
            }
        }

    }

    /**
     * Visszaadja a pimple container egy adott elemét.
     * @param string $key
     * @return mixed
     */
    public function getPimpleElement($key) {
        try {
            return $this->pimple[$key];
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Szolgáltatás hozzáadása.
     *
     * @param ServiceProviderInterface $provider
     */
    public function register(ServiceProviderInterface $provider) {
        $this->pimple->register($provider);
    }

    /**
     * Visszaadja, a Security objektumot. Ha nem létezik NULL-lal tér vissza.
     *
     * @deprecated
     * @return Security
     */
    public function getSecurity() {
        return $this->pimple["security"];
    }

    /**
     * Visszaadja, a Security objektumot. Ha nem létezik NULL-lal tér vissza.
     *
     * @return Security
     */
    public static function Security() {
        return Application::getInstance()->getPimpleElement("security");
    }

    /**
     * @deprecated
     * @return Logger
     */
    public function getLogger() {
        return $this->pimple["logger"];
    }

    /**
     * Visszaadja a Logger objektumot.
     * @return Logger
     */
    public static function Logger() {
        return Application::getInstance()->getPimpleElement("logger");
    }

    /**
     * @deprecated
     * @return SessionStorage
     */
    public function getSession() {
        return $this->pimple["session"];
    }

    /**
     * @return SessionStorage
     */
    public static function Session() {
        return Application::getInstance()->getPimpleElement("session");
    }

    /**
     * @deprecated
     * @return Twig
     */
    public function getTwig() {
        return $this->pimple["twig"];
    }

    /**
     * @return Twig
     */
    public static function Twig() {
        return Application::getInstance()->getPimpleElement("twig");
    }

    /**
     * @return Heartbeat
     */
    public function getHeartbeat() {
        return $this->pimple["heartbeat"];
    }

    public function addHeartbeatListener(HeartbeatListener $listener) {
        $this->getHeartbeat()->addListener($listener);
    }

    /**
     * Visszaadja a környezeti beállításokat.
     * @return array
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Visszaadja a környezeti beállításokat.
     */
    public static function Environment() {
        return Application::getInstance()->getEnvironment();
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
     * @return RouterInterface
     */
    public function getRouter()
    {
        return $this->router;
    }

    /**
     * @return RouterInterface
     */
    public static function Router() {
        return Application::getInstance()->getRouter();
    }

    /**
     * @param RouterInterface $router
     */
    public function setRouter($router)
    {
        $this->router = $router;
    }

    /**
     * @return Translator
     */
    public static function Translator() {
        return Application::getInstance()->getPimpleElement("translator");
    }

    /**
     * @param $callable
     */
    public function setErrorHandler($callable) {
        $this->callableErrorHandler = $callable;
    }

    private function loadController($controllerName) {
        $class_path = $this->environment["controllers_path"]."/".$controllerName.".php";
        if (file_exists($class_path)) {
            include $class_path;
            return true;
        } else {
            return false;
        }
    }

    /**
     * @return UrlGenerator
     */
    public static function UrlGenerator() {
        return Application::getInstance()->getPimpleElement("url_generator");
    }

    public static function Mailer() {
        return Application::getInstance()->getPimpleElement("mailer");
    }

    /**
     *
     */
    public function redirectHttps() {
        $redirect = "https://".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'];
        header("Location:$redirect");
    }

    /**
     * @param $uri
     */
    public function redirect($uri) {
        if(!isset($_SERVER['HTTPS'])) {
            $redirect = "http://".$_SERVER['HTTP_HOST'].$uri;
            header("Location:$redirect");
        }
        $redirect = ($_SERVER['HTTPS'] != "on" ? "http" : "https")."://".$_SERVER['HTTP_HOST'].$uri;
        header("Location:$redirect");
    }

}