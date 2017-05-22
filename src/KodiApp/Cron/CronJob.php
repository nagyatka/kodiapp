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
    /**
     * @return void
     */
    public function execute();

    /**
     * @return string
     */
    public function name();
}