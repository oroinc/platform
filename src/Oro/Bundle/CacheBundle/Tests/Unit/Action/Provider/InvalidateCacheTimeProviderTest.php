<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Action\Provider;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheScheduleArgumentsBuilderInterface;
use Oro\Bundle\CacheBundle\Action\Provider\InvalidateCacheTimeProvider;
use Oro\Bundle\CacheBundle\Action\Transformer\DateTimeToStringTransformerInterface;
use Oro\Bundle\CacheBundle\Command\InvalidateCacheScheduleCommand;
use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Entity\Schedule;

class InvalidateCacheTimeProviderTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var InvalidateCacheScheduleArgumentsBuilderInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scheduleArgsBuilder;

    /**
     * @var ScheduleManager|\PHPUnit\Framework\MockObject\MockObject
     */
    private $scheduleManager;

    /**
     * @var DateTimeToStringTransformerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $cronFormatTransformer;

    /**
     * @var InvalidateCacheTimeProvider
     */
    private $provider;

    protected function setUp()
    {
        $this->scheduleArgsBuilder = $this->createMock(InvalidateCacheScheduleArgumentsBuilderInterface::class);
        $this->scheduleManager = $this->createMock(ScheduleManager::class);
        $this->cronFormatTransformer = $this->createMock(DateTimeToStringTransformerInterface::class);

        $this->provider = new InvalidateCacheTimeProvider(
            $this->scheduleArgsBuilder,
            $this->scheduleManager,
            $this->cronFormatTransformer
        );
    }

    public function testGetByDataStorage()
    {
        $args = ['1', '2'];
        $schedule = new Schedule();
        $dateTime = new \DateTime();

        $this->scheduleArgsBuilder->expects(static::once())
            ->method('build')
            ->willReturn($args);

        $this->scheduleManager->expects(static::once())
            ->method('getSchedulesByCommandAndArguments')
            ->with(InvalidateCacheScheduleCommand::NAME, $args)
            ->willReturn([$schedule]);

        $this->cronFormatTransformer->expects(static::once())
            ->method('reverseTransform')
            ->willReturn($dateTime);

        static::assertSame(
            $dateTime,
            $this->provider->getByDataStorage(new InvalidateCacheDataStorage())
        );
    }

    public function testGetByDataStorageForNoSchedule()
    {
        $args = ['1', '2'];

        $this->scheduleArgsBuilder->expects(static::once())
            ->method('build')
            ->willReturn($args);

        $this->scheduleManager->expects(static::once())
            ->method('getSchedulesByCommandAndArguments')
            ->with(InvalidateCacheScheduleCommand::NAME, $args)
            ->willReturn([]);

        $this->cronFormatTransformer->expects(static::never())
            ->method('reverseTransform');

        static::assertNull(
            $this->provider->getByDataStorage(new InvalidateCacheDataStorage())
        );
    }
}
