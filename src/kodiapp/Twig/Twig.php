<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 21.
 * Time: 17:55
 */

namespace Model\Core\Twig;


use KodiApp\Application;

class Twig
{
    /**
     * @var array
     */
    private $configuration;

    /**
     * @var bool
     */
    private $useAjax;

    /**
     * Twig constructor.
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        $this->configuration = $configuration;

        $loader = new \Twig_Loader_Filesystem($configuration["path"]);
        $this->twig = new \Twig_Environment($loader);

        // Escape
        $escaper = new \Twig_Extension_Escaper('html');
        $this->twig->addExtension($escaper);

        //TODO: Twig függvények beállítása
        /*
        // Jogosultság function
        $is_granted = new \Twig_SimpleFunction('is_granted', function ($module_id) {
            return $this->getUser()->checkModulePermission($module_id);
        });
        $this->twig->addFunction($is_granted);
        $is_member = new \Twig_SimpleFunction('isMemberOf', function ($group_id) {
            return $this->getUser()->isMemberOf($group_id);
        });
        $this->twig->addFunction($is_member);
        */

        $this->useAjax =(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? false : true;
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
     *  $application->addPageFrameContent(new PageTitleContentProvider("Oldal címe"));
     *
     *  <title>{{ app.page_title }}</title> ==> <title>Oldal címe</title>
     *
     * @param $templateName
     * @param array $parameters
     * @param bool $forceRawTemplate
     */
    public function render($templateName,$parameters = [],$forceRawTemplate = false) {
        // Különböző contentek betöltése
        $contentProviders = Application::getInstance()->getPageContents();
        foreach ($contentProviders as $contentProvider) {
            $parameters["app"][$contentProvider->getKey()] = $contentProvider->getValue();
        }

        if($this->useAjax || $forceRawTemplate) {
            print $this->twig->render($templateName,$parameters);
        } else {
            $pageFrameName = $this->configuration["page_frame_template_path"];
            $parameters["app"]["content_template_name"] = $templateName;
            print $this->twig->render($pageFrameName,$parameters);
        }
    }




}