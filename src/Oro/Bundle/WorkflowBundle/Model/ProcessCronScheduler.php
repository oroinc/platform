<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessCronScheduler
{
    /** @var ManagerRegistry */
    private $registry;

    /** @var string */
    private $scheduleClass;

    /** @var string */
    private static $command = HandleProcessTriggerCommand::NAME;

    /** @var Schedule[] */
    private $created = [];

    /** @var Schedule[] */
    private $removed = [];

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

        $this->registry = $registry;
        $this->scheduleClass = $scheduleClass;
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

        $arguments = $this->buildArguments($trigger);

        $objectManager = $this->getObjectManager();

        if (!$this->scheduleManager->hasSchedule(self::$command, $arguments, $trigger->getCron())) {
            $schedule = $this->scheduleManager->createSchedule(self::$command, $arguments, $trigger->getCron());
            $objectManager->persist($schedule);
            $this->created[] = $schedule;
        }
    }

    public function remove(ProcessTrigger $trigger)
    {
        if (!$trigger->getCron()) {
            throw new \InvalidArgumentException(
                sprintf('%s supports only cron schedule triggers.', __CLASS__)
            );
        }

        $arguments = $this->buildArguments($trigger);

        $repository = $this->getRepository();

        if ($this->scheduleManager->hasSchedule(self::$command, $arguments, $trigger->getCron())) {
            $schedule = $this->scheduleManager->createSchedule(self::$command, $arguments, $trigger->getCron());

            $storedSchedule = $repository->findOneBy(['args_hash' => $schedule->getArgumentsHash()]);

            if ($storedSchedule) {
                $this->getObjectManager()->remove($storedSchedule);
            }
        }
    }

    /**
     * @param ProcessTrigger $trigger
     * @return array
     */
    protected function buildArguments(ProcessTrigger $trigger)
    {
        return [
            sprintf('--name=%s', $trigger->getDefinition()->getName()),
            sprintf('--id=%d', $trigger->getId())
        ];
    }

    /**
     * Applies schedule modifications to database
     * @return Schedule[] array of new Schedules
     */
    public function flush()
    {
        $new = $this->created;

        if (count($this->created) !== 0 || count($this->removed) !== 0) {
            $this->getObjectManager()->flush();
            $this->created = [];
            $this->removed = [];
        }

        return $new;
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getObjectManager()->getRepository($this->scheduleClass);
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectManager
     */
    protected function getObjectManager()
    {
        $objectManager = $this->registry->getManagerForClass($this->scheduleClass);

        if (!$objectManager) {
            throw new \InvalidArgumentException(
                'Please provide manageable schedule entity class'
            );
        }

        return $objectManager;
    }
}
