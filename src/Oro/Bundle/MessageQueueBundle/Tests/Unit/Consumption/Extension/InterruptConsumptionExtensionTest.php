<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\CacheState;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\InterruptConsumptionExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\Testing\TempDirExtension;
use Psr\Log\LoggerInterface;

class InterruptConsumptionExtensionTest extends \PHPUnit\Framework\TestCase
{
    use TempDirExtension;

    /** @var string */
    private $filePath;

    /** @var \PHPUnit\Framework\MockObject\MockObject|CacheState */
    protected $cacheState;

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

        $this->assertDirectoryNotExists($directory);
    }

    public function testShouldCreateFileIfItNotExist(): void
    {
        $this->assertFileDoesNotExist($this->filePath);

        new InterruptConsumptionExtension($this->filePath, $this->cacheState);

        $this->assertFileExists($this->filePath);
    }

    public function testShouldNotChangeFileMetadataIfItExistsOnConstruct(): void
    {
        touch($this->filePath, time() - 1);
        $timestamp = filemtime($this->filePath);

        new InterruptConsumptionExtension($this->filePath, $this->cacheState);

        clearstatcache(true, $this->filePath);

        $this->assertEquals($timestamp, filemtime($this->filePath));
    }

    public function testShouldInterruptConsumptionIfFileWasDeleted(): void
    {
        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);

        unlink($this->filePath);

        $context = $this->createContextMock();

        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'Execution interrupted: The cache was cleared.',
                ['context' => $context]
            );

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
    }

    public function testShouldInterruptConsumptionIfFileMetadataIncreased(): void
    {
        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);

        touch($this->filePath, time() + 1);

        $context = $this->createContextMock();

        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'Execution interrupted: The cache was invalidated.',
                ['context' => $context]
            );

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
    }

    public function testShouldNotInterruptIfFileExistAndMetadataNotChanged(): void
    {
        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);

        $context = $this->createContextMock();

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
        // set the cache change date in future,
        // the case when consumer works for 30 days and after that the cache was changed
        $changeStateDate = new \DateTime('now+30days', new \DateTimeZone('UTC'));

        $extension = new InterruptConsumptionExtension($this->filePath, $this->cacheState);
        $context = $this->createContextMock();

        $logger = $this->createLoggerMock();
        $logger->expects($this->once())
            ->method('info')
            ->with(
                'Execution interrupted: The cache has changed.',
                ['context' => $context]
            );

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
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|Context
     */
    private function createContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|LoggerInterface
     */
    private function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
