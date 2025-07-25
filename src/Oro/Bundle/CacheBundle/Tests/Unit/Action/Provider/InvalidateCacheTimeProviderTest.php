<?php

namespace Oro\Bundle\CacheBundle\Tests\Unit\Action\Provider;

use Oro\Bundle\CacheBundle\Action\DataStorage\InvalidateCacheDataStorage;
use Oro\Bundle\CacheBundle\Action\Handler\InvalidateCacheScheduleArgumentsBuilderInterface;
use Oro\Bundle\CacheBundle\Action\Provider\InvalidateCacheTimeProvider;
use Oro\Bundle\CacheBundle\Action\Transformer\DateTimeToStringTransformerInterface;
use Oro\Bundle\CronBundle\Entity\Manager\ScheduleManager;
use Oro\Bundle\CronBundle\Entity\Schedule;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class InvalidateCacheTimeProviderTest extends TestCase
{
    private InvalidateCacheScheduleArgumentsBuilderInterface&MockObject $scheduleArgsBuilder;
    private ScheduleManager&MockObject $scheduleManager;
    private DateTimeToStringTransformerInterface&MockObject $cronFormatTransformer;
    private InvalidateCacheTimeProvider $provider;

    #[\Override]
    protected function setUp(): void
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

    public function testGetByDataStorage(): void
    {
        $args = ['1', '2'];
        $schedule = new Schedule();
        $dateTime = new \DateTime();

        $this->scheduleArgsBuilder->expects(self::once())
            ->method('build')
            ->willReturn($args);

        $this->scheduleManager->expects(self::once())
            ->method('getSchedulesByCommandAndArguments')
            ->with('oro:cache:invalidate:schedule', $args)
            ->willReturn([$schedule]);

        $this->cronFormatTransformer->expects(self::once())
            ->method('reverseTransform')
            ->willReturn($dateTime);

        self::assertSame(
            $dateTime,
            $this->provider->getByDataStorage(new InvalidateCacheDataStorage())
        );
    }

    public function testGetByDataStorageForNoSchedule(): void
    {
        $args = ['1', '2'];

        $this->scheduleArgsBuilder->expects(self::once())
            ->method('build')
            ->willReturn($args);

        $this->scheduleManager->expects(self::once())
            ->method('getSchedulesByCommandAndArguments')
            ->with('oro:cache:invalidate:schedule', $args)
            ->willReturn([]);

        $this->cronFormatTransformer->expects(self::never())
            ->method('reverseTransform');

        self::assertNull(
            $this->provider->getByDataStorage(new InvalidateCacheDataStorage())
        );
    }
}
