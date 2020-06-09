<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\Extension\SignalExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class SignalExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var SignalExtension */
    private $extension;

    /** @var Context */
    private $context;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $session = $this->createMock(SessionInterface::class);
        $logger = $this->createMock(LoggerInterface::class);
        $this->context = new Context($session);
        $this->context->setLogger($logger);

        $this->extension = new SignalExtension();
        $this->extension->onStart($this->context);
    }

    /**
     * @dataProvider signalDataProvider
     *
     * @param string $signal
     */
    public function testOnBeforeReceive($signal)
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
     *
     * @param string $signal
     */
    public function testOnPostReceived($signal)
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
     *
     * @param string $signal
     */
    public function testIdle($signal)
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

    /**
     * @return array
     */
    public function signalDataProvider()
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

    /**
     * @param Context $context
     * @param string $signal
     */
    private function handleSignal(Context $context, $signal)
    {
        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $context->getLogger();
        $logger->expects($this->exactly(3))
            ->method('debug')
            ->will($this->returnValueMap([
                [sprintf('Caught signal: %s', $signal)],
                ['Interrupt consumption'],
                ['Interrupt execution'],
            ]));

        $this->extension->handleSignal($signal);
    }

    /**
     * @param Context $context
     * @param string $signal
     */
    private function handleInvalidSignal(Context $context, $signal)
    {
        /** @var LoggerInterface|\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $context->getLogger();
        $logger->expects($this->once())
            ->method('debug')
            ->with(sprintf('Caught signal: %s', $signal));

        $this->extension->handleSignal($signal);
    }
}
