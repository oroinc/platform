<?php

namespace Oro\Bundle\CacheBundle\Action\Provider;

use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheScheduleArgumentsBuilderInterface;
use Oro\Bundle\CacheBundle\Action\Transformer\DateTimeToStringTransformerInterface;
use Oro\Bundle\CacheBundle\DataStorage\DataStorageInterface;
use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Entity\Schedule;

/**
 * Provides datetime or Schedule for cache invalidation.
 */
class InvalidateCacheTimeProvider
{
    /**
     * @var InvalidateCacheScheduleArgumentsBuilderInterface
     */
    private $scheduleArgsBuilder;

    /**
     * @var ScheduleManager
     */
    private $scheduleManager;

    /**
     * @var DateTimeToStringTransformerInterface
     */
    private $cronFormatTransformer;

    public function __construct(
        InvalidateCacheScheduleArgumentsBuilderInterface $scheduleArgsBuilder,
        ScheduleManager $scheduleManager,
        DateTimeToStringTransformerInterface $cronFormatTransformer
    ) {
        $this->scheduleArgsBuilder = $scheduleArgsBuilder;
        $this->scheduleManager = $scheduleManager;
        $this->cronFormatTransformer = $cronFormatTransformer;
    }

    /**
     * @param DataStorageInterface $dataStorage
     *
     * @return \DateTime|null
     */
    public function getByDataStorage(DataStorageInterface $dataStorage)
    {
        $schedule = $this->getSchedule($dataStorage);
        if (!$schedule) {
            return null;
        }

        return $this->cronFormatTransformer->reverseTransform($schedule->getDefinition());
    }

    /**
     * @param DataStorageInterface $dataStorage
     *
     * @return Schedule|null
     */
    private function getSchedule(DataStorageInterface $dataStorage)
    {
        $args = $this->scheduleArgsBuilder->build($dataStorage);
        $schedules = $this->scheduleManager->getSchedulesByCommandAndArguments(
            'oro:cache:invalidate:schedule',
            $args
        );

        return array_shift($schedules);
    }
}
