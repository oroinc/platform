<?php

namespace Oro\Bundle\LoggerBundle\Tests\Unit\Monolog;

use Monolog\Handler\HandlerInterface;
use Monolog\Handler\NativeMailerHandler;
use Oro\Bundle\LoggerBundle\Monolog\DisableHandlerWrapper;
use Oro\Bundle\LoggerBundle\Monolog\LogLevelConfig;
use Oro\Bundle\LoggerBundle\Test\MonologTestCaseTrait;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class DisableHandlerWrapperTest extends TestCase
{
    use MonologTestCaseTrait;

    private LogLevelConfig|\PHPUnit\Framework\MockObject\MockObject $config;

    private HandlerInterface|\PHPUnit\Framework\MockObject\MockObject $inner;

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
    public function testIsHandling(bool $expected, bool $isActive): void
    {
        $this->config->expects(self::once())
            ->method('isActive')
            ->willReturn($isActive);
        self::assertEquals($expected, $this->wrapper->isHandling($this->getLogRecord()));
    }

    public function isHandlingProvider(): array
    {
        return [
            'enabled' => [true, false],
            'disabled' => [false, true],
        ];
    }

    public function testHandleBatch(): void
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

    public function testHandleBatchDisabled(): void
    {
        $this->config->expects(self::once())
            ->method('isActive')
            ->willReturn(true);
        $this->inner->expects(self::never())
            ->method('handleBatch');
        $this->wrapper->handleBatch($this->getMultipleLogRecords());
    }

    public function testMagicCall(): void
    {
        $event = new TerminateEvent(
            $this->createMock(HttpKernelInterface::class),
            new Request(),
            new Response()
        );
        $mailerHandler = $this->createMock(NativeMailerHandler::class);
        $mailerHandler->expects(self::once())
            ->method('addHeader')
            ->with($event);

        $wrapper = new DisableHandlerWrapper($this->config, $mailerHandler);
        $wrapper->addHeader($event);
    }
}
