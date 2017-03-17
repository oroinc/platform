<?php

namespace Oro\Bundle\CronBundle\Entity\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\CronBundle\Filter\SchedulesByArgumentsFilterInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

/**
 * Provide late management for Schedule entities
 * All modifications stored into memory and performed only when flush method is invoked
 */
class DeferredScheduler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var ScheduleManager */
    protected $scheduleManager;

    /** @var bool */
    protected $dirty = false;

    /** @var array|Schedule[] */
    protected $forRemove = [];

    /** @var array|Schedule[] */
    protected $forPersist = [];

    /** @var array */
    protected $lateArgumentsResolving = [];

    /** @var ManagerRegistry */
    private $registry;

    /** @var string */
    private $scheduleClass;

    /** @var SchedulesByArgumentsFilterInterface */
    private $schedulesByArgumentsFilter;

    /**
     * @param ScheduleManager $scheduleManager
     * @param ManagerRegistry $registry
     * @param SchedulesByArgumentsFilterInterface $schedulesByArgumentsFilter
     * @param string $scheduleClass
     */
    public function __construct(
        ScheduleManager $scheduleManager,
        ManagerRegistry $registry,
        SchedulesByArgumentsFilterInterface $schedulesByArgumentsFilter,
        $scheduleClass
    ) {
        $this->scheduleManager = $scheduleManager;
        $this->registry = $registry;
        $this->schedulesByArgumentsFilter = $schedulesByArgumentsFilter;
        $this->scheduleClass = $scheduleClass;
        $this->setLogger(new NullLogger());
    }

    /**
     * @param string $command
     * @param array|callable $arguments can be late resolving callback values (resolving will happen when flush invokes)
     * @param string $cronDefinition
     */
    public function addSchedule($command, $arguments, $cronDefinition)
    {
        if (is_callable($arguments)) {
            $this->lateArgumentsResolving[] = [$command, $arguments, $cronDefinition];
        } else {
            $newSchedule = $this->ensureCreate($command, $arguments, $cronDefinition);

            if ($newSchedule) {
                $this->forPersist[] = $newSchedule;
                $this->dirty = true;
            }
        }
    }

    /**
     * @param string $command
     * @param array $arguments
     * @param string $cronDefinition
     * @return null|Schedule
     */
    protected function ensureCreate($command, array $arguments, $cronDefinition)
    {
        $schedule = null;

        if (!$this->scheduleManager->hasSchedule($command, $arguments, $cronDefinition)) {
            $schedule = $this->scheduleManager->createSchedule($command, $arguments, $cronDefinition);
            $this->notify('created', $schedule);
        }

        return $schedule;
    }

    /**
     * @param string $command
     * @param array $arguments
     * @param string $cronDefinition
     */
    public function removeSchedule($command, array $arguments, $cronDefinition)
    {
        $schedules = $this->getRepository()->findBy(['command' => $command, 'definition' => $cronDefinition]);

        $this->removeSchedules($schedules, $arguments);
    }

    /**
     * @param string $command
     * @param array  $arguments
     */
    public function removeScheduleForCommand($command, array $arguments = [])
    {
        $schedules = $this->getRepository()->findBy(['command' => $command]);

        $this->removeSchedules($schedules, $arguments);
    }

    /**
     * @return ObjectManager
     */
    protected function getObjectManager()
    {
        $objectManager = $this->registry->getManagerForClass($this->scheduleClass);

        if (!$objectManager) {
            throw new \InvalidArgumentException('Please provide manageable schedule entity class');
        }

        return $objectManager;
    }

    /**
     * @param string $action
     * @param Schedule $schedule
     */
    protected function notify($action, Schedule $schedule)
    {
        $this->logger->info(sprintf(
            '>>> cron schedule [%s] %s %s - %s',
            $schedule->getDefinition(),
            $schedule->getCommand(),
            implode(' ', $schedule->getArguments()),
            $action
        ));
    }

    /**
     * @return ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getObjectManager()->getRepository($this->scheduleClass);
    }

    /**
     * Applies schedule modifications to database
     */
    public function flush()
    {
        if ($this->dirty || (count($this->lateArgumentsResolving) > 0)) {
            $objectManager = $this->getObjectManager();

            while ($toPersist = array_shift($this->forPersist)) {
                $objectManager->persist($toPersist);
            }

            while ($lateArguments = array_shift($this->lateArgumentsResolving)) {
                list($command, $argumentsCallback, $cron) = $lateArguments;
                $arguments = call_user_func($argumentsCallback);
                $schedule = $this->ensureCreate($command, $arguments, $cron);
                if ($schedule) {
                    $objectManager->persist($schedule);
                }
            }

            while ($toRemove = array_shift($this->forRemove)) {
                if ($objectManager->contains($toRemove)) {
                    $objectManager->remove($toRemove);
                }
            }

            $objectManager->flush();
            $this->dirty = false;
            $this->logger->info('>>> schedule modification persisted.');
        }
    }

    /**
     * @param array $schedules
     * @param array $arguments
     */
    private function removeSchedules(array $schedules, array $arguments)
    {
        $schedules = $this->schedulesByArgumentsFilter->filter($schedules, $arguments);

        if (count($schedules) !== 0) {
            foreach ($schedules as $schedule) {
                $this->forRemove[] = $schedule;
                $this->dirty = true;
                $this->notify('deleted', $schedule);
            }
        }
    }
}
