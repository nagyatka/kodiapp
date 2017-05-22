<?php

define( '_EXEC', 1 );
define('PATH_BASE', dirname(dirname(dirname(__FILE__))));

// Composer autoloader
require '../vendor/autoload.php';

class TempConf implements \KodiApp\ApplicationConfiguration {
    public function initializeApplication(\KodiApp\Application $application)
    {
        // TODO: Implement initializeApplication() method.
    }

}
class TempJob implements \KodiApp\Cron\CronJob {

    private $a;

    /**
     * TempJob constructor.
     * @param $a
     */
    public function __construct($a)
    {
        $this->a = $a;
    }


    public function execute()
    {
        print $this->a.": Hello world!\n";
    }

    public function name()
    {
        return "hello";
    }

}

class Job implements \KodiApp\Cron\CronJob {

    private $a;

    /**
     * TempJob constructor.
     * @param $a
     */
    public function __construct($a)
    {
        $this->a = $a;
    }


    public function execute()
    {
        print $this->a.": Hello world!\n";
    }

    public function name()
    {
        return "hello222";
    }

}

$app = new \KodiApp\CronApplication();

$app->addJob(new TempJob(1))->runImmediately();
$app->addJob(new TempJob(2))->runImmediately();
$app->addJob(new Job(44))->runDaily("14:57");

$app->run(new TempConf());