<?php

namespace Oro\Bundle\CacheBundle\Action\Handler;

use Oro\Bundle\CacheBundle\Action\Transformer\DateTimeToStringTransformerInterface;
use Oro\Bundle\CacheBundle\Command\InvalidateCacheScheduleCommand;
use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;
use Oro\Bundle\CronBundle\Entity\Manager\DeferredScheduler;

/**
 * Action handler for adding to schedule caching invalidation.
 */
class InvalidateCacheActionScheduledHandler implements InvalidateCacheActionHandlerInterface
{
    const PARAM_INVALIDATE_TIME = 'invalidateTime';
    const PARAM_HANDLER_SERVICE_NAME = 'service';

    /**
     * @var DeferredScheduler
     */
    private $deferredScheduler;

    /**
     * @var InvalidateCacheScheduleArgumentsBuilderInterface
     */
    private $scheduleArgumentsBuilder;

    /**
     * @var DateTimeToStringTransformerInterface
     */
    private $cronFormatTransformer;

    public function __construct(
        DeferredScheduler $deferredScheduler,
        InvalidateCacheScheduleArgumentsBuilderInterface $scheduleArgumentsBuilder,
        DateTimeToStringTransformerInterface $cronFormatTransformer
    ) {
        $this->deferredScheduler = $deferredScheduler;
        $this->scheduleArgumentsBuilder = $scheduleArgumentsBuilder;
        $this->cronFormatTransformer = $cronFormatTransformer;
    }

    public function handle(DataStorageInterface $dataStorage)
    {
        $scheduleTime = $dataStorage->get(self::PARAM_INVALIDATE_TIME);
        $command = InvalidateCacheScheduleCommand::getDefaultName();
        $args = $this->scheduleArgumentsBuilder->build($dataStorage);

        $this->deferredScheduler->removeScheduleForCommand($command, $args);

        if ($scheduleTime) {
            $this->deferredScheduler->addSchedule(
                $command,
                $args,
                $this->cronFormatTransformer->transform($scheduleTime)
            );
        }

        $this->deferredScheduler->flush();
    }
}
