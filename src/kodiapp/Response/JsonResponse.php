<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 21.
 * Time: 16:58
 */

namespace KodiApp\Response;


class JsonResponse
{

    private $values;

    /**
     * JsonResponse constructor.
     * @param $values
     */
    public function __construct($values)
    {
        $this->values = $values;
    }

    /**
     * The __toString method allows a class to decide how it will react when it is converted to a string.
     *
     * @return string
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring
     */
    function __toString()
    {
        try {
            $logger = Application::getInstance()->getLogger();
            $environment = Application::getInstance()->getEnvironment();

            header('Content-type: application/json');
            if ($environment == Application::ENV_DEVELOPMENT) {
                $t = number_format(microtime(1) - Application::getInstance()->getDebugStartTime(), 3, '.', ' ')." sec";
                $m = number_format(memory_get_peak_usage()/1024/1024, 2, '.', ' ')." MB";
                if (is_object($this->values)) $this->values["debug_execution_time"] = $t;
                $logger->addDebug("Exection time and memory usage", array($t, $m));
            }
            return json_encode($this->values, JSON_NUMERIC_CHECK);
        } catch (\Exception $e) {
            return Application::getInstance()->getEnvironment() == Application::ENV_DEVELOPMENT ? $e->getMessage() : "";
        }
    }


}