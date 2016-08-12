<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 11.
 * Time: 12:36
 */

namespace KodiApp\Translator;
use KodiApp\Application;
use KodiApp\Response\JsonResponse;
use Symfony\Component\Translation\Loader\ArrayLoader;
use Symfony\Component\Translation\Loader\JsonFileLoader;
use Symfony\Component\Translation\Loader\YamlFileLoader;

/**
 * Class Translator
 *
 * Az osztály feladata, hogy biztosítsa a több nyelvűsítést az alkalmazásban. Ehhez a symfony/translation csomagot
 * használja, amelyet csak minimálisan egészítettünk ki.
 *
 * A működéshez a következő paramétereket kell KÖTELEZÓEN beállítani:
 *
 *      - fallbackLocales:
 *          A nyelvek listája, amik elérhetőek az alkalmazásban.
 *          pl.:
 *              "fallbackLocales" => [
 *                  "hu","en"
 *               ],
 *
 *      - loader:
 *          A nyelvi fájlok formátuma, milyen típusú betöltőt kell használni. Itt megkülönböztetünk develepoment
 *          és production módot. Production módba érdemes a LOADER_SERIALIZED-t típust választani, ami szerializált
 *          tömbökben tárolt nyelvi fájlokat tud betölteni.
 *          pl.:
 *              "loader"   => [
 *                   "dev" => Translator::LOADER_ARRAY,
 *                   "prod"=> Translator::LOADER_SERIALIZED
 *              ],
 *
 *      - resources:
 *          A nyelvi fájlok elérési útvonala. Itt a kulcs mezőben a nyelv nevét kell megadni (meg kell egyeznie a
 *          a fallbackLocales-ban megadottal), majd utána fel kell sorolni a különböző útvonalakat.
 *          pl.:
 *              "resources" => [
 *                   "hu" => [
 *                      "/hu/login.yaml",
 *                      "/hu/menu.yaml",
 *                   ],
 *                   ...
 *              ]
 *
 *      - strategy:
 *           3 különböző stratégiát tud követni az aktuális nyelv kiválasztásánál.
 *
 *          STRATEGY_ONLY_COOKIE: Csak cookie-t használ a nyelv eldöntésére.
 *          STRATEGY_ONLY_URL: Csak az url-ben lévő locale alapján dönt a nyelvről. Ehhez a LanguageRouter
 *              router-t kell választani, ami képes kezelni az url-ben érkező locale-okat.
 *          STRATEGY_COOKIE_AND_URL: (ajánlott) Elsősorban az urlből próbálja betölteni, de ha az urlben nem érkezik, akkor
 *              cookie-ból próbálja beállítani. Ehhez a LanguageRouter router-t kell választani, ami képes kezelni az
 *              url-ben érkező locale-okat.
 *
 * STRATEGY_ONLY_COOKIE esetén KÖTELEZŐ paraméter:
 *      - cookie_set_url:
 *          Url, amelyen keresztül átváltható a cookieban lévő nyelv. Fontos, hogy GET HTTP metódussal és locale nevű
 *          paraméterben várja a beállítandó nyelvet nyelvet.
 *          pl.:
 *              "cookie_set_url" => "/lang/set/{locale:[a-z]+}"
 *
 *
 * Nyelvbeállítás alapelve:
 *  Ha megpróbálunk beállítani (setLocale metódus) egy nyelvet, először ellenőrzi, hogy az létezik-e a beállított
 * nyelvek között. Ha nem, megnézni, hogy van-e érvényes nyelv a HTTP_ACCEPT_LANGUAGE-ben. Ha ott sincs akkor az első
 * érvényes nyelvet fogja választani (~default => fallbackLocales[0] ).
 *
 *
 *
 * Példa konfiguráció:
 *
 * $application->register( new TranslatorProvider([
 *
 *   "fallbackLocales" => [
 *      "hu","en"
 *   ],
 *
 *   "loader"   => [
 *      "dev" => Translator::LOADER_ARRAY,
 *      "prod"=> Translator::LOADER_SERIALIZED
 *   ],
 *
 *   "resources" => [
 *      "hu" => [
 *          "/asd/asd",
 *      ]
 *   ],
 *
 *   "strategy"  => Translator::STRATEGY_ONLY_COOKIE,
 *
 *   //Required at STRATEGY_ONLY_COOKIE
 *   "cookie_set_url" => "/lang/set/{locale:[a-z]+}"
 *
 * ]));
 *
 *
 * Fordítás szerver oldalon:
 *
 *      $translatedText = Application::Translator()->trans("text");
 *
 * Fordítás kliens oldalon (twig fájlban):
 *
 *      {{ translate("text") }}
 *
 * Nyelv lekérdezése szerver oldalon:
 *
 *      $locale = Application::Translator()->getLocale();
 *
 * Nyelv lekérdezése kliens oldalon:
 *
 *      {{ get_locale() }}
 *
 *
 * @package KodiApp\Translator
 */
class Translator
{
    /*
     * Loader típusok
     */
    const LOADER_ARRAY      = "array";
    const LOADER_SERIALIZED = "serialize";
    const LOADER_JSON       = "json";
    const LOADER_YAML       = "yaml";

    const STRATEGY_ONLY_COOKIE      = "only_cookie";
    const STRATEGY_ONLY_URL         = "only_url";
    const STRATEGY_COOKIE_AND_URL   = "cookie_and_url";

    /**
     * @var \Symfony\Component\Translation\Translator
     */
    private $translator;

    /**
     * @var array
     */
    private $configuration;

    /**
     * Translator constructor.
     * @param array $configuration
     */
    public function __construct($configuration)
    {
        // Konfiguráció betöltése
        $this->configuration = $configuration;

        //Aktuális nyelv kiválasztása a stratégiától függően
        switch ($this->configuration["strategy"]) {
            case Translator::STRATEGY_ONLY_COOKIE:
                if(!isset($this->configuration["cookie_set_url"])) {
                    throw new \InvalidArgumentException("You must provide 'cookie_set_url' parameter in case of STRATEGY_ONLY_COOKIE");
                }
                // Locale beállítása
                $locale = $this->loadLocaleFromCookie();

                break;
            case Translator::STRATEGY_ONLY_URL:
                // Itt jelenleg nem kell csinálni semmit, mert a language router mindent elintéz, majd beállítja a
                // a megfelelő helyen
                $locale = $configuration["fallbackLocales"][0];
                break;
            case Translator::STRATEGY_COOKIE_AND_URL:
                // Locale beállítása
                $locale = $this->loadLocaleFromCookie();
                break;

            default:
                $locale = $configuration["fallbackLocales"][0];
        }

        //Symfony-s translator inicilaizálása
        $this->translator = new \Symfony\Component\Translation\Translator($locale);
        $this->translator->setFallbackLocales($this->configuration["fallbackLocales"]);

        //Loader és források inicializálás
        if(Application::isDevelopmentEnv()) {
            $format = $this->configuration["loader"]["dev"];
        } else {
            $format = $this->configuration["loader"]["prod"];
        }
        $this->translator->addLoader($format,$this->loaderFactory($format));
        foreach ($this->configuration["resources"] as $locale => $resources) {
            foreach ($resources as $resource) {
                $this->translator->addResource($format,$resource,$locale);
            }
        }
    }

    /**
     * Betölti a megfelelő nyelvi betöltőt.
     * @param string $loader
     * @return null|ArrayLoader|JsonFileLoader|YamlFileLoader
     * @throws \Exception
     */
    private function loaderFactory($loader) {
        switch ($loader) {
            case Translator::LOADER_ARRAY:
                return new ArrayLoader();
            case Translator::LOADER_JSON:
                return new JsonFileLoader();
            case Translator::LOADER_SERIALIZED:
                throw new \Exception("Not yet implemented");
                break;
            case Translator::LOADER_YAML:
                return new YamlFileLoader();
            default:
                return null;
        }
    }

    /**
     * Megpróbálja beállítani a paraméterben megadott locale-t. Ha nincsen, megpróbálja betölteni a HTTP_ACCEPT_LANGUAGE
     * szerver változóból a locale-t, de ha az nem támogatott, akkor legvégül a default nyelvet tölti be.
     *
     * Igaz a visszatérési érték, ha sikerült beállítani a paraméterben megadott locale-t. Minden más esetben hamis.
     *
     * @param string $locale
     * @return bool
     */
    public function setLocale($locale) {

        if($this->isSupportedLocale($locale)) {
            //Ha létezik ilyen támogatott locale, akkor beállítjuk
            $validLocale = $locale;
            $success = true;
        } else {
            //Ha nem létezik megpróbáljuk beállítani a HTTP_ACCEPT_LANGUAGE változóból
            $httpLocale = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if($this->isSupportedLocale($httpLocale)) {
                $validLocale = $httpLocale;
            } else {
                $validLocale = $this->configuration["fallbackLocales"][0];
            }
            $success = false;
        }

        $this->translator->setLocale($validLocale);
        // Adott stratégiák esetén be kell állítani a cookie-kat is
        if(
            $this->configuration["strategy"] == Translator::STRATEGY_ONLY_COOKIE ||
            $this->configuration["strategy"] == Translator::STRATEGY_COOKIE_AND_URL
        ) {
            $this->setLocaleToCookie($validLocale);
        }

        return $success;
    }

    /**
     * Kérés kezelő függvény, ne használd! Ha szeretnéd beállítani az aktuális locale-t, akkor a setLocale metódust használd.
     *
     * Visszatérési értéke JSON, amiben jelzi a művelet sikerességét.
     *
     * @param array $params
     * @return JsonResponse
     */
    final public function handleSetLocale($params) {
        $this->translator->setLocale($params["locale"]);
        return new JsonResponse([
            "success"   =>  true
        ]);
    }

    /**
     * Visszaadja az akutálisan beállított locale-t.
     *
     * @return string
     */
    public function getLocale() {
        return $this->translator->getLocale();
    }

    /**
    * (Symfony translator wrapper metódus) Sets the fallback locales.
    *
    * @param array $locales The fallback locales
    *
    * @throws \InvalidArgumentException If a locale contains invalid characters
    */
    public function setFallbackLocales(array $locales)
    {
        $this->translator->setFallbackLocales($locales);
    }

    /**
     * (Symfony translator wrapper metódus) Gets the fallback locales.
     *
     * @return array $locales The fallback locales
     */
    public function getFallbackLocales()
    {
        return $this->translator->getFallbackLocales();
    }

    /**
     * (Symfony translator wrapper metódus) Translates the given message.
     *
     * @param string      $id         The message id (may also be an object that can be cast to string)
     * @param array       $parameters An array of parameters for the message
     * @param string|null $domain     The domain for the message or null to use the default
     * @param string|null $locale     The locale or null to use the default
     *
     * @return string The translated string
     *
     * @throws \InvalidArgumentException If the locale contains invalid characters
     */
    public function trans($id, array $parameters = [], $domain = null, $locale = null) {
        return $this->translator->trans($id,$parameters,$domain,$locale);
    }

    /**
     * Visszaadja a fordításra hasz
     * @return \Symfony\Component\Translation\Translator
     */
    public function getSymfonyTranslator() {
        return $this->translator;
    }

    /**
     * Visszaadja az url tömböt a Router-nek, de csak akkor, ha a STRATEGY_ONLY_COOKIE stratégia van bekapcsolva.
     * @return array
     */
    final public function getCookieSetUrl() {
        if($this->configuration["strategy"] == Translator::STRATEGY_ONLY_COOKIE) {
            return [
                "method" => "GET",
                "url" => $this->configuration["cookie_set_url"],
                "handler" => Translator::class . "::handleSetLocale",
            ];
        } else {
            return null;
        }
    }

    /**
     * Visszadja a cookie-ban tárolt locale-t, ha van ilyen. Ha nincsen, megpróbálja betölteni a HTTP_ACCEPT_LANGUAGE
     * szerver változóból a locale-t, de ha az nem támogatott, akkor legvégül a default nyelvet tölti be.
     *
     * @return string
     */
    private function loadLocaleFromCookie() {
        //Megpróbáljuk betölteni
        if(isset($_COOKIE["locale"])) {
            return $_COOKIE["locale"];
        }
        //Ha nincs, akkor az első fallback-et próbáljuk
        else {
            //Ha nem létezik megpróbáljuk beállítani a HTTP_ACCEPT_LANGUAGE változóból
            $httpLocale = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if($this->isSupportedLocale($httpLocale)) {
                return $httpLocale;
            } else {
                return $this->configuration["fallbackLocales"][0];
            }
        }
    }

    /**
     * Elmenti cookie-ba a paraméterben kapott locale-t. FONTOS: önmagában nem végez locale validálást, emiatt nem
     * ajánlott közvetlen meghívni. Javasolt metódus a setLocale, amely ha szükséges meghívja a metódust.
     *
     * @param string $locale
     */
    private function setLocaleToCookie($locale) {

        if($this->isSupportedLocale($locale)) {
            //Ha létezik ilyen támogatott locale, akkor beállítjuk
            $_COOKIE["locale"] = $locale;
        } else {
            //Ha nem létezik megpróbáljuk beállítani a HTTP_ACCEPT_LANGUAGE változóból
            $httpLocale = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 2);
            if($this->isSupportedLocale($httpLocale)) {
                $_COOKIE["locale"] = $httpLocale;
            } else {
                $_COOKIE["locale"] = $this->configuration["fallbackLocales"][0];
            }
        }
    }

    /**
     * Megvizsgálja, hogy a paraméterben kapott locale benne van-e a konfigurációban megadott tömbben. Ha igen, true
     * amúgy false a visszatérési érték.
     *
     * @param $locale
     * @return bool
     */
    public function isSupportedLocale($locale) {
        return in_array($locale,$this->configuration["fallbackLocales"]);
    }

}