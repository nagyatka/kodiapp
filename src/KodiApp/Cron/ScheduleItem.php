<?php
/**
 * Created by PhpStorm.
 * User: nagyatka
 * Date: 2017. 05. 22.
 * Time: 9:26
 */

namespace KodiApp\Cron;


class ScheduleItem
{
    /**
     * @var CronJob
     */
    private $job;

    /**
     * @var int
     */
    private $schedulingTime;

    /**
     * ScheduleItem constructor.
     * @param CronJob $job
     */
    public function __construct(CronJob $job)
    {
        $this->job = $job;
    }

    /**
     * @param string $atTime
     * @return void
     */
    public function runDaily($atTime = null) {

        // Ha az atTime == null, akkor az azt jelenti, hogy mindennap éjfélkor kell futtatni.
        if($atTime == null) {
            $atTime = date("Y-m-d");
        }

        // Lekérdezzük, hogy mikor futtattuk utoljára
        $lastExecution = Scheduler::getInstance()->getJobFromSchedulingTable($this->job->name());

        // Ha még nem futott korábban és a mai napból már eltelt annyi
        // VAGY
        // Ha az adott pillanat és az utolsó futás között nagyobb a különbség mint egy nap, akkor futtatható
        if( ($lastExecution == null && strtotime($atTime) < time()) || ($lastExecution != null && (strtotime($atTime) - $lastExecution > 86400))) {
            $this->schedulingTime = strtotime($atTime);
            Scheduler::getInstance()->addItem($this);
        }
    }

    /**
     *
     */
    public function runImmediately() {
        $this->schedulingTime = time();
        Scheduler::getInstance()->addItem($this);
    }

    /**
     * @return int
     */
    public function getSchedulingTime()
    {
        return $this->schedulingTime;
    }

    /**
     * @return CronJob
     */
    public function getJob()
    {
        return $this->job;
    }


}