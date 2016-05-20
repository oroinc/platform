<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;

use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessTriggerScheduler
{
    /**
     * @var ScheduleManager
     */
    private $scheduleManager;

    /**
     * @var string
     */
    private static $command = HandleProcessTriggerCommand::NAME;

    /**
     * @var ObjectManager
     */
    private $objectManager;

    /**
     * @var Schedule[]
     */
    private $created = [];

    /**
     * @param ScheduleManager $scheduleManager
     * @param ManagerRegistry $registry
     * @param string $scheduleClass
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(ScheduleManager $scheduleManager, ManagerRegistry $registry, $scheduleClass)
    {
        $this->scheduleManager = $scheduleManager;

        $this->objectManager = $registry->getManagerForClass($scheduleClass);

        if (!$this->objectManager) {
            throw new \InvalidArgumentException(
                'Please provide manageable entity schedule class'
            );
        }
    }

    /**
     * @param ProcessTrigger $trigger
     *
     * @throws \InvalidArgumentException
     */
    public function add(ProcessTrigger $trigger)
    {
        if (!$trigger->getCron()) {
            throw new \InvalidArgumentException(
                sprintf('%s supports only cron schedule triggers.', __CLASS__)
            );
        }

        $arguments = [
            sprintf('--name=%s', $trigger->getDefinition()->getName()),
            sprintf('--id=%d', $trigger->getId())
        ];

        if (!$this->scheduleManager->hasSchedule(self::$command, $arguments, $trigger->getCron())) {
            $schedule = $this->scheduleManager->createSchedule(self::$command, $arguments, $trigger->getCron());
            $this->objectManager->persist($schedule);
            $this->created[] = $schedule;
        }
    }

    /**
     * Stores all newly created schedules in database
     * @return Schedule[] array of new Schedules
     */
    public function flush()
    {
        $new = $this->created;

        if (count($this->created) !== 0) {
            $this->objectManager->flush();
            $this->created = [];
        }

        return $new;
    }
}
