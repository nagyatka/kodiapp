<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2017. 05. 19.
 * Time: 21:32
 */

namespace KodiApp\Cron;

interface CronJob
{
    public function execute();
}