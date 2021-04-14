<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Monolog\Handler\HandlerInterface;
use Oro\Bundle\LoggerBundle\Monolog\DisableHandlerWrapper;
use Oro\Bundle\LoggerBundle\Monolog\LogLevelConfig;
use Oro\Bundle\LoggerBundle\Test\MonologTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Monolog\Handler\SwiftMailerHandler;
use Symfony\Component\HttpKernel\Event\PostResponseEvent;

class DisableHandlerWrapperTest extends TestCase
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

    private DisableHandlerWrapper $wrapper;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(HandlerInterface::class);
        $this->config = $this->createMock(LogLevelConfig::class);
        $this->wrapper = new DisableHandlerWrapper($this->config, $this->inner);
    }

    /**
     * @dataProvider isHandlingProvider
     */
    public function testIsHandling(bool $expected, bool $isActive)
    {
        $this->config->expects(self::once())
            ->method('isActive')
            ->willReturn($isActive);
        $this->assertEquals($expected, $this->wrapper->isHandling($this->getLogRecord()));
    }

    public function isHandlingProvider()
    {
        return [
            'enabled' => [true, false],
            'disabled' => [false, true],
        ];
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
        $this->wrapper->handleBatch($records);
    }

    public function testHandleBatchDisabled()
    {
        $this->config->expects(self::once())
            ->method('isActive')
            ->willReturn(true);
        $this->inner->expects(self::never())
            ->method('handleBatch');
        $this->wrapper->handleBatch($this->getMultipleLogRecords());
    }

    public function testMagicCall()
    {
        $event = $this->createMock(PostResponseEvent::class);
        $mailerHandler = $this->createMock(SwiftMailerHandler::class);
        $mailerHandler->expects(self::once())
            ->method('onKernelTerminate')
            ->with($event);

        $wrapper = new DisableHandlerWrapper($this->config, $mailerHandler);
        $wrapper->onKernelTerminate($event);
    }
}
