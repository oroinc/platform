<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\SignalExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SignalExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var Context */
    private $context;

    /** @var SignalExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->context = new Context($this->createMock(SessionInterface::class));
        $this->context->setLogger($this->createMock(LoggerInterface::class));

        $this->extension = new SignalExtension();
        $this->extension->onStart($this->context);
    }

    /**
     * @dataProvider signalDataProvider
     */
    public function testOnBeforeReceive(int $signal)
    {
        $context = clone $this->context;
        $this->handleSignal($context, $signal);

        $this->extension->onBeforeReceive($context);

        $this->assertTrue($context->isExecutionInterrupted());
        $this->assertEquals('Interrupt execution.', $context->getInterruptedReason());
    }

    public function testOnBeforeReceiveInvalidSignal()
    {
        $context = clone $this->context;
        $this->handleInvalidSignal($context, SIGALRM);

        $this->extension->onBeforeReceive($context);

        $this->assertFalse($context->isExecutionInterrupted());
        $this->assertNull($context->getInterruptedReason());
    }

    /**
     * @dataProvider signalDataProvider
     */
    public function testOnPostReceived(int $signal)
    {
        $context = clone $this->context;
        $this->handleSignal($context, $signal);

        $this->extension->onPostReceived($context);

        $this->assertTrue($context->isExecutionInterrupted());
        $this->assertEquals('Interrupt execution.', $context->getInterruptedReason());
    }

    public function testOnPostReceivedInvalidSignal()
    {
        $context = clone $this->context;
        $this->handleInvalidSignal($context, SIGALRM);

        $this->extension->onPostReceived($context);

        $this->assertFalse($context->isExecutionInterrupted());
        $this->assertNull($context->getInterruptedReason());
    }

    /**
     * @dataProvider signalDataProvider
     */
    public function testIdle(int $signal)
    {
        $context = clone $this->context;
        $this->handleSignal($context, $signal);

        $this->extension->onIdle($context);

        $this->assertTrue($context->isExecutionInterrupted());
        $this->assertEquals('Interrupt execution.', $context->getInterruptedReason());
    }

    public function testOnIdleInvalidSignal()
    {
        $context = clone $this->context;
        $this->handleInvalidSignal($context, SIGALRM);

        $this->extension->onIdle($context);

        $this->assertFalse($context->isExecutionInterrupted());
        $this->assertNull($context->getInterruptedReason());
    }

    public function signalDataProvider(): array
    {
        return [
            'SIGTERM' => [
                'signal' => SIGTERM,
            ],
            'SIGQUIT' => [
                'signal' => SIGQUIT,
            ],
            'SIGINT' => [
                'signal' => SIGINT,
            ],
        ];
    }

    private function handleSignal(Context $context, int $signal)
    {
        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $context->getLogger();
        $logger->expects($this->exactly(3))
            ->method('debug')
            ->willReturnMap([
                [sprintf('Caught signal: %s', $signal)],
                ['Interrupt consumption'],
                ['Interrupt execution'],
            ]);

        $this->extension->handleSignal($signal);
    }

    private function handleInvalidSignal(Context $context, int $signal)
    {
        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $context->getLogger();
        $logger->expects($this->once())
            ->method('debug')
            ->with(sprintf('Caught signal: %s', $signal));

        $this->extension->handleSignal($signal);
    }
}
