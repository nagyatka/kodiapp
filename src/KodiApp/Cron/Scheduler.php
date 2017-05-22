<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2017. 05. 22.
 * Time: 9:27
 */

namespace KodiApp\Cron;


class Scheduler
{

    /**
     * @var Scheduler
     */
    private static $instance = null;

    /**
     * @var array
     */
    private $schedulingTable;

    /**
     * @var ScheduleItem[]
     */
    private $items = [];

    /**
     * Scheduler constructor.
     */
    private function __construct() {
        $this->loadSchedulingTable();
    }

    public static function getInstance() {
        if(self::$instance == null) {
            self::$instance = new Scheduler();
        }
        return self::$instance;
    }

    public static function run() {
        $scheduler = self::getInstance();
        try {
            foreach ($scheduler->getItems() as $item) {
                $job = $item->getJob();
                $job->execute();
                $scheduler->setJobToSchedulingTable($job->name(),$item->getSchedulingTime());
            }
        } catch (\Exception $e) {
            print "[ERR_CODE:".$e->getCode()."]: ".$e->getMessage()."\n";
        } finally {
            $scheduler->saveSchedulingTable();
        }
    }

    /**
     *
     */
    private function loadSchedulingTable() {
        if(!file_exists("scheduling_table.json")) {
            $this->schedulingTable = [];
        } else {
            $this->schedulingTable = json_decode(file_get_contents("scheduling_table.json"),true);
        }
    }

    /**
     *
     */
    private function saveSchedulingTable() {
        $fp = fopen('scheduling_table.json', 'w');
        fwrite($fp, json_encode($this->schedulingTable));
        fclose($fp);
    }

    /**
     * @param $jobName
     * @return int|null
     */
    public function getJobFromSchedulingTable($jobName) {
        return isset($this->schedulingTable[$jobName]) ? $this->schedulingTable[$jobName]:null;
    }

    /**
     * @param $jobName
     * @param $time
     */
    private function setJobToSchedulingTable($jobName,$time) {
        $this->schedulingTable[$jobName] = $time;
    }

    /**
     * @param ScheduleItem $item
     */
    public function addItem(ScheduleItem $item) {
        $this->items[] = $item;
    }

    /**
     * @return ScheduleItem[]
     */
    public function getItems()
    {
        return $this->items;
    }


}