<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Monolog\Handler\FingersCrossed\ActivationStrategyInterface;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use Oro\Bundle\LoggerBundle\Monolog\ConfigurableFingersCrossedHandler;
use Oro\Bundle\LoggerBundle\Monolog\LogLevelConfig;
use Oro\Bundle\LoggerBundle\Test\MonologTestCaseTrait;
use PHPUnit\Framework\TestCase;

class ConfigurableFingersCrossedHandlerTest extends TestCase
{
    use MonologTestCaseTrait;

    private ConfigurableFingersCrossedHandler $handler;

    /**
     * @var LogLevelConfig|\PHPUnit\Framework\MockObject\MockObject
     */
    private $config;

    /**
     * @var ActivationStrategyInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    private $activationStrategy;

    private TestHandler $testNestedHandler;

    protected function setUp(): void
    {
        $this->config = $this->createMock(LogLevelConfig::class);
        $this->activationStrategy = $this->createMock(ActivationStrategyInterface::class);
        $this->testNestedHandler = new TestHandler();
        $this->handler = new ConfigurableFingersCrossedHandler($this->testNestedHandler, $this->activationStrategy);
        $this->handler->setLogLevelConfig($this->config);
    }

    public function testHandleInactiveConfig()
    {
        $this->config->expects(self::once())
            ->method('isActive')
            ->willReturn(false);
        $this->activationStrategy->expects(self::once())
            ->method('isHandlerActivated')
            ->willReturn(true);

        $record = $this->getLogRecord(Logger::ERROR);

        $this->handler->handle($record);

        $this->assertTrue(
            $this->testNestedHandler->hasError($record)
        );
    }

    public function testHandleActiveConfig()
    {
        $this->config->expects(self::once())
            ->method('isActive')
            ->willReturn(true);

        $this->activationStrategy->expects(self::never())
            ->method('isHandlerActivated');

        $record = $this->getLogRecord(Logger::ERROR);

        $this->handler->handle($record);

        $this->assertTrue(
            $this->testNestedHandler->hasError($record)
        );
    }

    public function testHandleBatchActiveConfig()
    {
        $this->config->expects(self::atLeastOnce())
            ->method('isActive')
            ->willReturn(true);
        $this->config->expects(self::atLeastOnce())
            ->method('getMinLevel')
            ->willReturn(Logger::INFO);
        $this->activationStrategy->expects(self::never())
            ->method('isHandlerActivated');

        $records = $this->getMultipleLogRecords();

        $this->handler->handleBatch($records);

        $this->assertFalse($this->testNestedHandler->hasDebugRecords());
        $this->assertCount(3, $this->testNestedHandler->getRecords());
    }

    public function testHandleBatchInactiveConfig()
    {
        $this->config->expects(self::atLeastOnce())
            ->method('isActive')
            ->willReturn(false);
        $this->activationStrategy->expects(self::atLeastOnce())
            ->method('isHandlerActivated')
            ->willReturn(true);

        $records = $this->getMultipleLogRecords();

        $this->handler->handleBatch($records);

        $this->assertCount(5, $this->testNestedHandler->getRecords());
    }

    public function testReset()
    {
        $this->config->expects(self::atLeastOnce())
            ->method('isActive')
            ->willReturn(false);
        $this->activationStrategy->expects(self::atLeastOnce())
            ->method('isHandlerActivated')
            ->willReturn(true);
        $records = $this->getMultipleLogRecords();

        $this->handler->handleBatch($records);
        $this->assertCount(5, $this->testNestedHandler->getRecords());

        $this->handler->reset();
        $this->assertEmpty($this->testNestedHandler->getRecords());
    }
}
