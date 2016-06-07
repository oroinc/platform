<?php

namespace Oro\Bundle\WorkflowBundle\Model;

use Doctrine\Common\Persistence\ManagerRegistry;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;

use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Entity\Schedule;
use Oro\Bundle\WorkflowBundle\Command\HandleProcessTriggerCommand;
use Oro\Bundle\WorkflowBundle\Entity\ProcessTrigger;

class ProcessTriggerCronScheduler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    /** @var string */
    private static $command = HandleProcessTriggerCommand::NAME;

    /** @var ScheduleManager */
    protected $scheduleManager;

    /** @var bool */
    protected $dirty = false;

    /** @var ManagerRegistry */
    private $registry;

    /** @var string */
    private $scheduleClass;

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
        $this->setLogger(new NullLogger());
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
            $this->dirty = true;
            $this->notify('created', $schedule);
        }
    }

    /**
     * @param ProcessTrigger $trigger
     */
    public function removeSchedule(ProcessTrigger $trigger)
    {
        if (!$trigger->getCron()) {
            throw new \InvalidArgumentException(
                sprintf('%s supports only cron schedule triggers.', __CLASS__)
            );
        }

        $schedules = $this->getRepository()->findBy(
            ['command' => self::$command, 'definition' => $trigger->getCron()]
        );

        $arguments = $this->buildArguments($trigger);

        $schedules = array_filter($schedules, function (Schedule $schedule) use ($arguments) {
            $scheduleArgs = $schedule->getArguments();
            sort($scheduleArgs);
            return $scheduleArgs == $arguments;
        });

        if (count($schedules) !== 0) {
            $objectManager = $this->getObjectManager();

            foreach ($schedules as $schedule) {
                $objectManager->remove($schedule);
                $this->dirty = true;
                $this->notify('deleted', $schedule);
            }
        }
    }

    /**
     * @param ProcessTrigger $trigger
     *
     * @return array
     */
    protected function buildArguments(ProcessTrigger $trigger)
    {
        $args = [
            sprintf('--name=%s', $trigger->getDefinition()->getName()),
            sprintf('--id=%d', $trigger->getId())
        ];
        sort($args);
        return $args;
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

    /**
     * @param string $action
     * @param Schedule $schedule
     */
    protected function notify($action, Schedule $schedule)
    {
        $this->logger->info(sprintf(
            '>>> process trigger cron schedule [%s] %s %s - %s',
            $schedule->getDefinition(),
            $schedule->getCommand(),
            implode(' ', $schedule->getArguments()),
            $action
        ));
    }

    /**
     * @return \Doctrine\Common\Persistence\ObjectRepository
     */
    protected function getRepository()
    {
        return $this->getObjectManager()->getRepository($this->scheduleClass);
    }

    /**
     * Applies schedule modifications to database
     *
     * @return Schedule[] array of new Schedules
     */
    public function flush()
    {
        if ($this->dirty) {
            $this->getObjectManager()->flush();
            $this->dirty = false;
            $this->logger->info('>>> process trigger schedule modification persisted.');
        }
    }
}
