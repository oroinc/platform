<?php

namespace Oro\Bundle\MessageQueueBundle\Tests\Unit\Consumption\Extension;

use Psr\Log\LoggerInterface;

use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\SessionInterface;

use Oro\Bundle\MessageQueueBundle\Consumption\ConsumerHeartbeat;
use Oro\Bundle\MessageQueueBundle\Consumption\Extension\ConsumerHeartbeatExtension;

class ConsumerHeartbeatExtensionTest extends \PHPUnit_Framework_TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $consumerState;

    /** @var ConsumerHeartbeatExtension */
    protected $extension;

    /** @var Context */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $logger;

    protected function setUp()
    {
        $this->consumerState = $this->createMock(ConsumerHeartbeat::class);
        $this->extension = new ConsumerHeartbeatExtension(15, $this->consumerState);

        $this->context = new Context($this->createMock(SessionInterface::class));
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->context->setLogger($this->logger);
    }

    public function testOnBeforeReceiveOnStartConsumption()
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Update the consumer state time.');
        $this->consumerState->expects($this->once())
            ->method('tick');

        $this->extension->onBeforeReceive($this->context);
    }

    public function testOnBeforeReceiveWithNonExpiredPeriod()
    {
        $this->logger->expects($this->once())
            ->method('info')
            ->with('Update the consumer state time.');
        $this->consumerState->expects($this->once())
            ->method('tick');

        $this->extension->onBeforeReceive($this->context);
        $this->extension->onBeforeReceive($this->context);
    }

    public function testOnBeforeReceiveWithTurnedOffFunctionality()
    {
        $this->extension = new ConsumerHeartbeatExtension(0, $this->consumerState);

        $this->logger->expects($this->never())
            ->method('info');
        $this->consumerState->expects($this->never())
            ->method('tick');

        $this->extension->onBeforeReceive($this->context);
        $this->extension->onBeforeReceive($this->context);
    }
}
