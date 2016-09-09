<?php

namespace Oro\Bundle\CronBundle\Entity\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Oro\Bundle\CronBundle\Entity\Schedule;

class ScheduleManager
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $scheduleClass;

    /**
     * @param ManagerRegistry $registry
     * @param string $scheduleClass
     */
    public function __construct(ManagerRegistry $registry, $scheduleClass)
    {
        $this->registry = $registry;
        $this->scheduleClass = $scheduleClass;
    }

    /**
     * @param string $command
     * @param array $arguments
     * @param string $definition
     * @return bool
     */
    public function hasSchedule($command, array $arguments, $definition)
    {
        $schedules = $this->getRepository()->findBy(['command' => $command, 'definition' => $definition]);

        $argumentsSchedule = new Schedule();
        $argumentsSchedule->setArguments($arguments);

        $schedules = array_filter($schedules, function (Schedule $schedule) use ($argumentsSchedule) {
            return $schedule->getArgumentsHash() === $argumentsSchedule->getArgumentsHash();
        });

        return count($schedules) > 0;
    }

    /**
     * @param string $command
     * @param array $arguments
     * @param string $definition
     * @return Schedule
     */
    public function createSchedule($command, array $arguments, $definition)
    {
        if (!$command || !$definition) {
            throw new \InvalidArgumentException('Parameters "command" and "definition" must be specified.');
        }

        if ($this->hasSchedule($command, $arguments, $definition)) {
            throw new \LogicException('Schedule with same parameters already exists.');
        }

        $schedule = new Schedule();
        $schedule
            ->setCommand($command)
            ->setArguments($arguments)
            ->setDefinition($definition);

        return $schedule;
    }

    /**
     * @return ObjectManager
     */
    protected function getEntityManager()
    {
        return $this->registry->getManagerForClass($this->scheduleClass);
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getEntityManager()->getRepository($this->scheduleClass);
    }
}
