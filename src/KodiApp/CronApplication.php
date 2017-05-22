<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2017. 05. 19.
 * Time: 21:29
 */

namespace KodiApp;


use KodiApp\Cron\CronJob;
use KodiApp\Cron\ScheduleItem;
use KodiApp\Cron\Scheduler;

class CronApplication
{
    /**
     * @var Application
     */
    private $application;

    public function __construct()
    {
        $this->application = Application::getInstance();
    }


    public function addJob(CronJob $cronJob)
    {
        return new ScheduleItem($cronJob);
    }

    /**
     * @param ApplicationConfiguration $configuration
     */
    public function run(ApplicationConfiguration $configuration)
    {
        try {

            // Initialize environment
            if (!$configuration) {
                throw new \Exception("Missing configuration");
            }
            $configuration->initializeApplication($this->application);

            // Run scheduler
            Scheduler::run();

        } catch (\Exception $exception) {
            print $exception->getMessage();
        }
    }
}
