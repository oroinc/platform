<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Monolog\Handler\FilterHandler;
use Monolog\Handler\HandlerInterface;
use Monolog\Logger;
use Oro\Bundle\LoggerBundle\Monolog\DisableFilterHandlerWrapper;
use Oro\Bundle\LoggerBundle\Monolog\LogLevelConfig;
use Oro\Bundle\LoggerBundle\Test\MonologTestCaseTrait;
use PHPUnit\Framework\TestCase;

class DisableFilterHandlerWrapperTest extends TestCase
{
    use MonologTestCaseTrait;

    /**
     * @var LogLevelConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;

    /**
     * @var HandlerInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $inner;

    private DisableFilterHandlerWrapper $wrapper;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(FilterHandler::class);
        $this->config = $this->createMock(LogLevelConfig::class);
        $this->wrapper = new DisableFilterHandlerWrapper($this->config, $this->inner);
    }

    public function testHandleBatch()
    {
        $this->config->expects(self::once())
            ->method('isActive')
            ->willReturn(false);
        $records = $this->getMultipleLogRecords();
        $this->inner->expects(self::once())
            ->method('handleBatch')
            ->with($records);
        $this->inner->expects(self::never())
            ->method('setAcceptedLevels');
        $this->wrapper->handleBatch($records);
    }

    public function testHandleBatchDisabled()
    {
        $records = $this->getMultipleLogRecords();
        $this->config->expects(self::once())
            ->method('isActive')
            ->willReturn(true);
        $this->inner->expects(self::once())
            ->method('handleBatch')
            ->with($records);
        $this->inner->expects(self::once())
            ->method('setAcceptedLevels');
        $this->wrapper->handleBatch($records);
    }

    public function testGetAcceptedLevels()
    {
        $this->inner->expects(self::once())
            ->method('getAcceptedLevels');
        $this->wrapper->getAcceptedLevels();
    }

    public function testSetAcceptedLevels()
    {
        $this->inner->expects(self::once())
            ->method('setAcceptedLevels')
            ->with(Logger::INFO, Logger::WARNING);
        $this->wrapper->setAcceptedLevels(Logger::INFO, Logger::WARNING);
    }

    public function testReset()
    {
        $this->inner->expects(self::once())
            ->method('reset');
        $this->wrapper->reset();
    }
}
