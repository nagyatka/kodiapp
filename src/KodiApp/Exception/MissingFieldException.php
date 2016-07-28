<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 20.
 * Time: 14:18
 */

namespace KodiApp\Exception;

/**
 * Class MissingFieldException
 * Hiányzó adatbázis mezők ellenőrzésére is hibajelzésére használandó osztály.
 * @package Model\Exception
 */
class MissingFieldException extends \Exception
{
    /**
     * MissingFieldException constructor.
     * Akkor használjuk, ha hiányzik egy mező, ami az inserthez elengedhetetlen
     * @param string $fieldNames
     */
    public function __construct($fieldNames)
    {
        parent::__construct("Missing field name(s): ".implode(',',$fieldNames));
    }

    /**
     * Ellenőrzi, hogy az első paraméterben megadott listából minden paraméter szerepel-e a második tömbben, illetve,
     * hogy annak értéke nem NULL.
     * Ha talál hiányzó mezőt, MissingFieldException-t dob.
     *
     * @param array $fieldNames Mező nevek tömbje
     * @param array $parameters Mezők, amiket be akarunk illeszteni.
     * @throws MissingFieldException
     */
    public static function checkMissingFields($fieldNames,$parameters) {
        $missingFields = [];
        foreach ($fieldNames as $fieldName) {
            if(!isset($parameters[$fieldName])) $missingFields[] = $fieldName;
        }
        if(count($missingFields) > 0) throw new MissingFieldException($missingFields);
    }
}