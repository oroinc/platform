<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Oro\Bundle\MessageQueueBundle\Consumption\ConsumerHeartbeat;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ConsumerHeartbeatExtension;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class ConsumerHeartbeatExtensionTest extends TestCase
{
    private MockObject|ConsumerHeartbeat $consumerHeartbeat;

    private MockObject|LoggerInterface $logger;

    private ConsumerHeartbeatExtension $extension;
    private Context $context;

    #[\Override]
    protected function setUp(): void
    {
        $this->consumerHeartbeat = $this->createMock(ConsumerHeartbeat::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->context = new Context($this->createMock(SessionInterface::class));
        $this->context->setLogger($this->logger);

        $this->extension = new ConsumerHeartbeatExtension(15, $this->consumerHeartbeat);
    }

    public function testOnBeforeReceiveOnStartConsumption(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Update the consumer state time.');
        $this->consumerHeartbeat->expects($this->once())
            ->method('tick');

        $session = $this->createMock(SessionInterface::class);
        $context = new Context($session);
        $context->setLogger($this->logger);

        $this->extension->onStart($context);
        $this->extension->onBeforeReceive($context);
    }

    public function testOnBeforeReceiveWithNonExpiredPeriod(): void
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Update the consumer state time.');
        $this->consumerHeartbeat->expects($this->once())
            ->method('tick');

        $session = $this->createMock(SessionInterface::class);
        $context = new Context($session);
        $context->setLogger($this->logger);

        $this->extension->onStart($context);
        $this->extension->onBeforeReceive($context);
        $this->extension->onBeforeReceive($context);
    }

    public function testOnBeforeReceiveWithTurnedOffFunctionality(): void
    {
        $extension = new ConsumerHeartbeatExtension(0, $this->consumerHeartbeat);

        $this->logger->expects($this->never())
            ->method('info');
        $this->consumerHeartbeat->expects($this->never())
            ->method('tick');

        $session = $this->createMock(SessionInterface::class);
        $context = new Context($session);
        $context->setLogger($this->logger);

        $extension->onStart($context);
        $extension->onBeforeReceive($context);
        $extension->onBeforeReceive($context);
    }
}
