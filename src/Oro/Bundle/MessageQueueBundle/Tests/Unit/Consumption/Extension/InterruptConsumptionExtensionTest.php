<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\InterruptConsumptionExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\Testing\Logger\BufferingLogger;
use Oro\Component\Testing\TempDirExtension;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

class InterruptConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    private string $filePath;

    private CacheState|\PHPUnit\Framework\MockObject\MockObject $cacheState;

    protected function setUp(): void
    {
        $this->filePath = $this->getTempDir('InterruptConsumptionExtensionTest')
            . DIRECTORY_SEPARATOR
            . 'interrupt.tmp';

        $this->cacheState = $this->createMock(CacheState::class);
    }

    protected function tearDown(): void
    {
        $directory = dirname($this->filePath);

        @\unlink($this->filePath);
        @\rmdir($directory);

        self::assertDirectoryDoesNotExist($directory);
    }

    public function testShouldCreateFileIfItNotExist(): void
    {
        self::assertFileDoesNotExist($this->filePath);

        new InterruptConsumptionExtension($this->filePath, $this->cacheState);

        self::assertFileExists($this->filePath);
    }

    public function testShouldNotChangeFileMetadataIfItExistsOnConstruct(): void
    {
        touch($this->filePath, time() - 1);
        $timestamp = filemtime($this->filePath);

        new InterruptConsumptionExtension($this->filePath, $this->cacheState);

        clearstatcache(true, $this->filePath);

        self::assertEquals($timestamp, filemtime($this->filePath));
    }

    public function testShouldInterruptConsumptionIfFileWasDeleted(): void
    {
        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);

        unlink($this->filePath);

        $context = $this->createMock(Context::class);

        $logger = new BufferingLogger();

        $context->expects($this->once())
            ->method('getLogger')
            ->willReturn($logger);
        $context->expects($this->once())
            ->method('setExecutionInterrupted')
            ->with($this->isTrue());
        $context->expects($this->once())
            ->method('setInterruptedReason')
            ->with('The cache was cleared.');

        $extension->onBeforeReceive($context);

        self::assertEquals(
            [
                [
                    'info',
                    'Execution interrupted: The cache was cleared.',
                    ['context' => $context],
                ]
            ],
            $logger->cleanLogs()
        );
    }

    public function testShouldInterruptConsumptionIfFileMetadataIncreased(): void
    {
        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);

        touch($this->filePath, time() + 1);

        $context = $this->createMock(Context::class);

        $logger = new BufferingLogger();

        $context->expects($this->once())
            ->method('getLogger')
            ->willReturn($logger);
        $context->expects($this->once())
            ->method('setExecutionInterrupted')
            ->with($this->isTrue());
        $context->expects($this->once())
            ->method('setInterruptedReason')
            ->with('The cache was invalidated.');

        $extension->onStart($context);
        $extension->onBeforeReceive($context);

        self::assertEquals(
            [
                [
                    'info',
                    'Execution interrupted: The cache was invalidated.',
                    ['context' => $context],
                ]
            ],
            $logger->cleanLogs()
        );
    }

    public function testShouldNotInterruptIfFileExistAndMetadataNotChanged(): void
    {
        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);

        $context = $this->createMock(Context::class);

        $context->expects($this->never())
            ->method('getLogger');
        $context->expects($this->never())
            ->method('setExecutionInterrupted');
        $context->expects($this->never())
            ->method('setInterruptedReason');

        $extension->onStart($context);
        $extension->onBeforeReceive($context);
    }

    public function testShouldInterruptInCaseIfCacheWasChanged(): void
    {
        // set the cache change date in the future,
        // the case when consumer works for 30 days and after that the cache was changed
        $changeStateDate = new \DateTime('now+30days', new \DateTimeZone('UTC'));

        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);
        $context = $this->createMock(Context::class);

        $logger = new BufferingLogger();

        $context->expects($this->once())
            ->method('getLogger')
            ->willReturn($logger);
        $context->expects($this->once())
            ->method('setExecutionInterrupted')
            ->with($this->isTrue());
        $context->expects($this->once())
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

        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);
        $extension->setInterruptConsumptionCache($interruptConsumptionCache);
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

        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);
        $extension->setInterruptConsumptionCache($interruptConsumptionCache);
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

        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);
        $extension->setInterruptConsumptionCache($interruptConsumptionCache);
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

        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);
        $extension->setInterruptConsumptionCache($interruptConsumptionCache);

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
}
