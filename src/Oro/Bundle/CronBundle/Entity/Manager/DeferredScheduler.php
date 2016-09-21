<?php

namespace Oro\Bundle\CronBundle\Entity\Manager;

use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\Common\Persistence\ObjectManager;
use Doctrine\Common\Persistence\ObjectRepository;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use Oro\Bundle\CronBundle\Entity\Schedule;

/**
 * Provide late management for Schedule entities
 * All modifications stored into memory and performed only when flush method is invoked
 * @package Oro\Bundle\WorkflowBundle\Model
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

    /** @var ManagerRegistry */
    private $registry;

    /** @var string */
    private $scheduleClass;

    /**
     * @param ScheduleManager $scheduleManager
     * @param ManagerRegistry $registry
     * @param string $scheduleClass
     */
    public function __construct(ScheduleManager $scheduleManager, ManagerRegistry $registry, $scheduleClass)
    {
        $this->scheduleManager = $scheduleManager;

        $this->registry = $registry;
        $this->scheduleClass = $scheduleClass;
        $this->setLogger(new NullLogger());
    }

    /**
     * @param string $command
     * @param array $arguments
     * @param string $cronDefinition
     */
    public function addSchedule($command, array $arguments, $cronDefinition)
    {
        if (!$this->scheduleManager->hasSchedule($command, $arguments, $cronDefinition)) {
            $schedule = $this->scheduleManager->createSchedule($command, $arguments, $cronDefinition);
            $this->forPersist[] = $schedule;
            $this->dirty = true;
            $this->notify('created', $schedule);
        }
    }

    /**
     * @param string $command
     * @param array $arguments
     * @param string $cronDefinition
     */
    public function removeSchedule($command, array $arguments, $cronDefinition)
    {
        $schedules = $this->getRepository()->findBy(
            ['command' => $command, 'definition' => $cronDefinition]
        );

        $argsSchedule = new Schedule();
        $argsSchedule->setArguments($arguments);

        $schedules = array_filter($schedules, function (Schedule $schedule) use ($argsSchedule) {
            return $schedule->getArgumentsHash() === $argsSchedule->getArgumentsHash();
        });

        if (count($schedules) !== 0) {
            foreach ($schedules as $schedule) {
                $this->forRemove[] = $schedule;
                $this->dirty = true;
                $this->notify('deleted', $schedule);
            }
        }
    }

    /**
     * @return ObjectManager
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
        if ($this->dirty) {
            $objectManager = $this->getObjectManager();

            while ($toPersist = array_shift($this->forPersist)) {
                $objectManager->persist($toPersist);
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
}