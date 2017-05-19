<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2017. 05. 19.
 * Time: 21:29
 */

namespace KodiApp;


use KodiApp\Cron\CronJob;

class CronApplication extends Application
{
    /**
     * @var CronJob[]
     */
    private $jobs = [];

    public function addJob(CronJob $cronJob) {
        $this->jobs[] = $cronJob;
    }

    public function run(ApplicationConfiguration $configuration)
    {
        try {

            if(!$configuration) {
                throw new \Exception("Missing configuration");
            }

            $configuration->initializeApplication($this);

            foreach ($this->jobs as $job) {
                $job->execute();
            }

        } catch (\Exception $exception) {
            print $exception->getMessage();
        }
    }
}