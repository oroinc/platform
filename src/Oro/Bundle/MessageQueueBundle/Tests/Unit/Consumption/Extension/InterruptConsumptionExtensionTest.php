<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\InterruptConsumptionExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\Testing\Logger\BufferingLogger;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class InterruptConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    private CacheState|\PHPUnit\Framework\MockObject\MockObject $cacheState;

    protected function setUp(): void
    {
        $this->cacheState = $this->createMock(CacheState::class);
    }

    public function testShouldSaveCacheItemIfItNotExist(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(false);
        $cacheItem->expects(self::once())
            ->method('set')
            ->with(true);

        $interruptConsumptionCache = $this->createMock(CacheItemPoolInterface::class);
        $interruptConsumptionCache->expects(self::once())
            ->method('getItem')
            ->with(InterruptConsumptionExtension::CACHE_KEY)
            ->willReturn($cacheItem);
        $interruptConsumptionCache->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        new InterruptConsumptionExtension($interruptConsumptionCache, $this->cacheState);
    }

    public function testShouldNotSaveCacheIfItExistsOnConstruct(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(self::once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem->expects(self::never())
            ->method('set')
            ->withAnyParameters();

        $interruptConsumptionCache = $this->createMock(CacheItemPoolInterface::class);
        $interruptConsumptionCache->expects(self::once())
            ->method('getItem')
            ->with(InterruptConsumptionExtension::CACHE_KEY)
            ->willReturn($cacheItem);
        $interruptConsumptionCache->expects(self::never())
            ->method('save')
            ->withAnyParameters();

        new InterruptConsumptionExtension($interruptConsumptionCache, $this->cacheState);
    }

    public function testShouldInterruptConsumptionIfCacheIsEmpty(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(self::exactly(2))
            ->method('isHit')
            ->willReturn(false);
        $cacheItem->expects(self::once())
            ->method('set')
            ->with(true);

        $interruptConsumptionCache = $this->createMock(CacheItemPoolInterface::class);
        $interruptConsumptionCache->expects(self::exactly(2))
            ->method('getItem')
            ->with(InterruptConsumptionExtension::CACHE_KEY)
            ->willReturn($cacheItem);
        $interruptConsumptionCache->expects(self::once())
            ->method('save')
            ->with($cacheItem);

        $logger = new BufferingLogger();

        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getLogger')
            ->willReturn($logger);
        $context->expects(self::once())
            ->method('setExecutionInterrupted')
            ->with(true);
        $context->expects(self::once())
            ->method('setInterruptedReason')
            ->with('The cache has changed.');

        $extension = new InterruptConsumptionExtension($interruptConsumptionCache, $this->cacheState);
        $extension->onBeforeReceive($context);

        self::assertEquals(
            [
                [
                    'info',
                    'Execution interrupted: The cache has changed.',
                    ['context' => $context],
                ]
            ],
            $logger->cleanLogs()
        );
    }

    public function testShouldNotInterruptIfCacheExists(): void
    {
        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(self::exactly(2))
            ->method('isHit')
            ->willReturn(true);
        $cacheItem->expects(self::never())
            ->method('set')
            ->withAnyParameters();

        $interruptConsumptionCache = $this->createMock(CacheItemPoolInterface::class);
        $interruptConsumptionCache->expects(self::exactly(2))
            ->method('getItem')
            ->with(InterruptConsumptionExtension::CACHE_KEY)
            ->willReturn($cacheItem);
        $interruptConsumptionCache->expects(self::never())
            ->method('save')
            ->withAnyParameters();

        $extension = new InterruptConsumptionExtension($interruptConsumptionCache, $this->cacheState);

        $logger = new BufferingLogger();

        $context = $this->createMock(Context::class);
        $context->expects(self::never())
            ->method('getLogger')
            ->withAnyParameters();
        $context->expects(self::never())
            ->method('setExecutionInterrupted')
            ->withAnyParameters();
        $context->expects(self::never())
            ->method('setInterruptedReason')
            ->withAnyParameters();

        $extension->onStart($context);
        $extension->onBeforeReceive($context);

        self::assertEmpty($logger->cleanLogs());
    }

    public function testShouldInterruptInCaseIfCacheWasChanged(): void
    {
        // set the cache change date in the future,
        // the case when consumer works for 30 days and after that the cache was changed
        $changeStateDate = new \DateTime('now+30days', new \DateTimeZone('UTC'));

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects(self::exactly(2))
            ->method('isHit')
            ->willReturn(true);
        $cacheItem->expects(self::never())
            ->method('set')
            ->withAnyParameters();

        $interruptConsumptionCache = $this->createMock(CacheItemPoolInterface::class);
        $interruptConsumptionCache->expects(self::exactly(2))
            ->method('getItem')
            ->with(InterruptConsumptionExtension::CACHE_KEY)
            ->willReturn($cacheItem);
        $interruptConsumptionCache->expects(self::never())
            ->method('save')
            ->withAnyParameters();

        $extension = new InterruptConsumptionExtension($interruptConsumptionCache, $this->cacheState);

        $logger = new BufferingLogger();

        $context = $this->createMock(Context::class);
        $context->expects(self::once())
            ->method('getLogger')
            ->willReturn($logger);
        $context->expects(self::once())
            ->method('setExecutionInterrupted')
            ->with(true);
        $context->expects(self::once())
            ->method('setInterruptedReason')
            ->with('The cache has changed.');

        $this->cacheState->expects(self::once())
            ->method('getChangeDate')
            ->willReturn($changeStateDate);

        $extension->onStart($context);
        $extension->onBeforeReceive($context);

        self::assertEquals(
            [
                [
                    'info',
                    'Execution interrupted: The cache has changed.',
                    ['context' => $context],
                ]
            ],
            $logger->cleanLogs()
        );
    }
}
