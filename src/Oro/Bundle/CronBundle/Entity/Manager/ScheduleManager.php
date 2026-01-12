<?php

namespace Oro\Bundle\CronBundle\Entity\Manager;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Filter\SchedulesByArgumentsFilterInterface;

/**
 * Manages CRON job schedule entities in the database.
 *
 * This manager provides operations for creating, checking existence, and retrieving
 * Schedule entities that define when and how console commands should be executed by the cron system.
 * It handles the complexity of matching schedules by command name, cron definition, and command arguments,
 * using argument hashing to ensure accurate identification of schedules even when arguments are
 * provided in different orders.
 *
 * Key responsibilities:
 * - Checking if a schedule already exists for a given command, arguments, and cron definition
 * - Creating new schedule entities with validation to prevent duplicates
 * - Retrieving schedules filtered by command name and arguments
 * - Delegating argument-based filtering to {@see SchedulesByArgumentsFilterInterface}
 *
 * This manager is typically used by:
 * - {@see DeferredScheduler} for batch schedule operations
 * - Cron command definition loaders
 * - Workflow and process trigger schedulers
 */
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
     * @param SchedulesByArgumentsFilterInterface $schedulesByArgumentsFilter
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
