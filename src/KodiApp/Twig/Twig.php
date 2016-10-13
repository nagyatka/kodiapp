<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 21.
 * Time: 17:55
 */

namespace KodiApp\Twig;


use KodiApp\Application;
use KodiApp\ContentProvider\ContentProvider;
use KodiApp\Exception\HttpInternalErrorException;
use Twig_Extension_Debug;

/**
 * Class Twig
 *
 * Az osztály feladata a html tartalom renderelése a Twig könyvtár használatával.
 *
 * A twig objektumot az Application::Twig() osztály metódus hívásával lehet elérni.
 *
 * A html tartalmat az osztály render metódusával lehet generálni. Részletes leírás erről a metódus dokumentációjában.
 *
 * FONTOS:
 *   - Az app Twig változó foglalt!
 *
 * @package KodiApp\Twig
 */
class Twig
{
    /**
     * Konfigurációs tömb
     * @var array
     */
    private $configuration;

    /**
     * Ajaxos-e a kérés vagy sem
     * @var bool
     */
    private $useAjax;

    /**
     * @var ContentProvider[]
     */
    private $contentProviders;

    /**
     * Twig constructor.
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        //Konfiguráció betöltése
        $this->configuration = $configuration;
        $this->contentProviders = [];
        if(isset($this->configuration["content_providers"])) {
            $this->addContentProvider($this->configuration["content_providers"]);
            unset($this->configuration["content_providers"]);
        }

        // Twig inicializálása
        $loader = new \Twig_Loader_Filesystem($configuration["path"]);
        $this->twig = new \Twig_Environment($loader,[
            "debug" => Application::isDevelopmentEnv()
        ]);

        // Ajax csekkolása
        $this->useAjax =(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ?  true : false;

        // Escape
        $escaper = new \Twig_Extension_Escaper('html');
        $this->twig->addExtension($escaper);

        if(Application::isDevelopmentEnv())
            $this->twig->addExtension(new Twig_Extension_Debug());

        // Saját függvények definiálása
        $this->initializeBaseTwigFunction();
    }

    /**
     * ContentProvider hozzáadása.
     *
     * @param ContentProvider | ContentProvider[] $contents
     */
    public function addContentProvider($contents) {
        if($contents == null) {
            throw new \InvalidArgumentException("You must provide at least one content provider in contents");
        }
        if(is_array($contents)) {
            $this->contentProviders = array_merge($this->contentProviders , $contents);
        } else {
            $this->contentProviders[] = $contents;
        }
    }

    /**
     * A twig segítségével lerendereli a html tartalmat. AJAX kérések esetén csak a megadott template-t rendereli ki,
     * viszont ha nem AJAX kérést kapott a szerver, akkor a Twig inicializálásánál megadott oldalkeretbe tölti bele a
     * template-t.
     *
     * Ha mindenképpen azt szeretnénk, hogy csak a template fájl renderelődjön ki, akkor a $forceRawTemplate paramétert
     * true-ra kell állítani!
     *
     * A renderelésnél elérhető az összes olyan paraméter, amit az alkalmazás addPageFrameContent függvényén keresztül
     * lett beállítva. A Twig fájlokban ezek az 'app.*' változó néven keresztül érhetőek el.
     * Példa:
     *  $twig->addContentProvider(new PageTitleContentProvider("Oldal címe"));
     *
     *  <title>{{ app.page_title }}</title> ==> <title>Oldal címe</title>
     *
     * @param $templateName
     * @param array $parameters
     * @param bool $forceRawTemplate
     * @param null $desiredFrameTemplate
     * @throws HttpInternalErrorException
     */
    public function render($templateName,$parameters = [],$forceRawTemplate = false,$desiredFrameTemplate = null) {
        // Különböző contentek betöltése
        foreach ($this->contentProviders as $contentProvider) {
            $parameters["app"][$contentProvider->getKey()] = $contentProvider->getValue();
        }
        if($this->useAjax || $forceRawTemplate) {
            print $this->twig->render($templateName,$parameters);
        } else {
            if(is_array($this->configuration["page_frame_template_path"])) {
                $templates = $this->configuration["page_frame_template_path"];
                $actualRoute = Application::Router()->getActualRoute();
                if($desiredFrameTemplate != null) {
                    $desiredFrame = $desiredFrameTemplate;
                }
                elseif(isset($actualRoute["page_frame"])) {
                    $desiredFrame = Application::Router()->getActualRoute()["page_frame"];
                }
                else {
                    $desiredFrame = "default";
                }

                if(array_key_exists($desiredFrame,$templates)) {
                    $pageFrameName = $templates[$desiredFrame];
                }
                else {
                    throw new HttpInternalErrorException("Undefined page_frame template path in twig. Check configuration");
                }
            } else {
                $pageFrameName = $this->configuration["page_frame_template_path"];
            }
            $parameters["app"]["content_template_name"] = $templateName;
            print $this->twig->render($pageFrameName,$parameters);
        }
    }

    /**
     *  Betölti a twigbe az általunk definiált függvényeket.
     */
    private function initializeBaseTwigFunction() {
        // Development-e a környezet
        $is_dev = new \Twig_SimpleFunction('is_dev', function(){
            return Application::isDevelopmentEnv();
        });
        $this->twig->addFunction($is_dev);

        /*
         * A további twig függvényeket az átláthatóság szempontjából ide tegyük. Célszerű amúgy a Service providerekben
         * extendelni a twiget.
         */
    }

    /**
     * Visszaadja a belső twig környezetet.
     * @return \Twig_Environment
     */
    public function getTwigEnvironment() {
        return $this->twig;
    }



}