<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2016. 07. 24.
 * Time: 15:52
 */

namespace KodiApp;


interface ApplicationConfiguration
{
    public function initializeApplication(Application $application);
}