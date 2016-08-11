<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 08. 11.
 * Time: 12:17
 */

namespace KodiApp\Translator\Loader;


use Symfony\Component\Translation\Loader\FileLoader;


class SerializedFileLoader extends FileLoader
{
    protected function loadResource($resource)
    {
        $messages = array();
        if ($data = file_get_contents($resource)) {
            $messages = unserialize($data);
        }

        return $messages;
    }

}