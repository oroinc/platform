<?php

namespace Oro\Bundle\CronBundle\Entity\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Filter\SchedulesByArgumentsFilterInterface;

class ScheduleManager
{
    /** @var ManagerRegistry */
    protected $registry;

    /** @var string */
    protected $scheduleClass;

    /** @var SchedulesByArgumentsFilterInterface */
    private $schedulesByArgumentsFilter;

    /**
     * @param ManagerRegistry $registry
     * @param SchedulesByArgumentsFilterInterface $schedulesByArgumentsFilter,
     * @param string $scheduleClass
     */
    public function __construct(
        ManagerRegistry $registry,
        SchedulesByArgumentsFilterInterface $schedulesByArgumentsFilter,
        $scheduleClass
    ) {
        $this->registry = $registry;
        $this->schedulesByArgumentsFilter = $schedulesByArgumentsFilter;
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

        $schedules = $this->schedulesByArgumentsFilter->filter($schedules, $arguments);

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
     * @param string $command
     * @param string[] $arguments
     *
     * @return Schedule[]
     */
    public function getSchedulesByCommandAndArguments($command, array $arguments)
    {
        $schedules = $this->getRepository()->findBy(['command' => $command]);

        return $this->schedulesByArgumentsFilter->filter($schedules, $arguments);
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
