<?php

namespace Oro\Bundle\CacheBundle\Action\Handler;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorageInterface;
use Oro\Bundle\CacheBundle\Command\InvalidateCacheScheduleCommand;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;

class InvalidateCacheActionScheduledHandler implements InvalidateCacheActionHandlerInterface
{
    const PARAM_INVALIDATE_TIME = 'invalidateTime';
    const PARAM_HANDLER_SERVICE_NAME = 'service';

    /**
     * @var DeferredScheduler
     */
    private $deferredScheduler;

    /**
     * @param DeferredScheduler $deferredScheduler
     */
    public function __construct(DeferredScheduler $deferredScheduler)
    {
        $this->deferredScheduler = $deferredScheduler;
    }

    /**
     * @param InvalidateCacheDataStorageInterface $dataStorage
     */
    public function handle(InvalidateCacheDataStorageInterface $dataStorage)
    {
        $scheduleTime = $dataStorage->get(self::PARAM_INVALIDATE_TIME);
        $command = InvalidateCacheScheduleCommand::NAME;
        $args = $this->buildCommandArguments($dataStorage);

        $this->deferredScheduler->removeScheduleForCommand($command, $args);

        if ($scheduleTime) {
            $this->deferredScheduler->addSchedule(
                $command,
                $args,
                $this->convertDatetimeToCron($scheduleTime)
            );
        }

        $this->deferredScheduler->flush();
    }

    /**
     * @param InvalidateCacheDataStorageInterface $dataStorage
     *
     * @return array
     */
    private function buildCommandArguments(InvalidateCacheDataStorageInterface $dataStorage)
    {
        $excludeParameters = [
            self::PARAM_INVALIDATE_TIME,
            self::PARAM_HANDLER_SERVICE_NAME,
        ];

        $parameters = [];
        foreach ($dataStorage->all() as $key => $value) {
            if (!in_array($key, $excludeParameters, true)) {
                $parameters[$key] = $value;
            }
        }

        return [
            sprintf('%s=%s', self::PARAM_HANDLER_SERVICE_NAME, $dataStorage->get(self::PARAM_HANDLER_SERVICE_NAME)),
            sprintf('%s=%s', InvalidateCacheScheduleCommand::ARGUMENT_PARAMETERS, serialize($parameters))
        ];
    }

    /**
     * @param \DateTime $datetime
     *
     * @return string
     */
    private function convertDatetimeToCron(\DateTime $datetime)
    {
        return sprintf(
            '%d %d %d %d *',
            $datetime->format('i'),
            $datetime->format('H'),
            $datetime->format('d'),
            $datetime->format('m')
        );
    }
}
