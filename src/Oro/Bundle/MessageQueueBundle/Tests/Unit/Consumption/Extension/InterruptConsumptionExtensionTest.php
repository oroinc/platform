<?php
namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Psr\Log\LoggerInterface;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\InterruptConsumptionExtension;
use Oro\Component\MessageQueue\Consumption\Context;

class InterruptConsumptionExtensionTest extends \PHPUnit_Framework_TestCase
{
    protected $filePath;

    protected function setUp()
    {
        $directory = __DIR__ . '/temp/';
        @mkdir($directory, 0777);

        $this->filePath = $directory . 'interrupt.tmp';
    }

    protected function tearDown()
    {
        $directory = dirname($this->filePath);

        @unlink($this->filePath);
        rmdir($directory);

        parent::tearDown();
    }

    public function testCouldBeConstructedWithRequiredArguments()
    {
        new InterruptConsumptionExtension($this->filePath);
    }

    public function testShouldCreateFileIfItNotExist()
    {
        $this->assertFileNotExists($this->filePath);

        new InterruptConsumptionExtension($this->filePath);

        $this->assertFileExists($this->filePath);
    }

    public function testShouldNotChangeFileMetadataIfItExistsOnConstruct()
    {
        touch($this->filePath, time() - 1);
        $timestamp = filemtime($this->filePath);

        new InterruptConsumptionExtension($this->filePath);

        clearstatcache(true, $this->filePath);

        $this->assertEquals($timestamp, filemtime($this->filePath));
    }

    public function testShouldInterruptConsumptionIfFileWasDeleted()
    {
        $extension = new InterruptConsumptionExtension($this->filePath);

        unlink($this->filePath);

        $context = $this->createContextMock();

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('cache was cleared'),
                ['context' => $context]
            )
        ;

        $context
            ->expects($this->once())
            ->method('getLogger')
            ->willReturn($logger)
        ;
        $context
            ->expects($this->once())
            ->method('setExecutionInterrupted')
            ->with($this->isTrue())
        ;

        $extension->onBeforeReceive($context);
    }

    public function testShouldInterruptConsumptionIfFileMetadataIncreased()
    {
        $extension = new InterruptConsumptionExtension($this->filePath);

        touch($this->filePath, time() + 1);

        $context = $this->createContextMock();

        $logger = $this->createLoggerMock();
        $logger
            ->expects($this->once())
            ->method('info')
            ->with(
                $this->stringContains('cache was invalidated'),
                ['context' => $context]
            )
        ;

        $context
            ->expects($this->once())
            ->method('getLogger')
            ->willReturn($logger)
        ;
        $context
            ->expects($this->once())
            ->method('setExecutionInterrupted')
            ->with($this->isTrue())
        ;

        $extension->onBeforeReceive($context);
    }

    public function testShouldNotInterruptIfFileExistAndMetadataNotChanged()
    {
        $extension = new InterruptConsumptionExtension($this->filePath);

        $context = $this->createContextMock();

        $context
            ->expects($this->never())
            ->method('getLogger')
        ;
        $context
            ->expects($this->never())
            ->method('setExecutionInterrupted')
        ;

        $extension->onBeforeReceive($context);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|Context
     */
    protected function createContextMock()
    {
        return $this->createMock(Context::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|LoggerInterface
     */
    protected function createLoggerMock()
    {
        return $this->createMock(LoggerInterface::class);
    }
}
