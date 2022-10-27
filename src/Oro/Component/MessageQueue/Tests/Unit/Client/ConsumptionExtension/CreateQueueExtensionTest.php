<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\ConsumptionExtension;

use Oro\Component\MessageQueue\Client\ConsumptionExtension\CreateQueueExtension;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Consumption\Context;
use Oro\Component\MessageQueue\Transport\QueueCollection;
use Oro\Component\MessageQueue\Transport\QueueInterface;
use Oro\Component\MessageQueue\Transport\SessionInterface;
use Psr\Log\LoggerInterface;

class CreateQueueExtensionTest extends \PHPUnit\Framework\TestCase
{
    /** @var \PHPUnit\Framework\MockObject\MockObject|DriverInterface */
    private $driver;

    /** @var CreateQueueExtension */
    private $extension;

    protected function setUp(): void
    {
        $this->driver = $this->createMock(DriverInterface::class);

        $this->extension = new CreateQueueExtension($this->driver, new QueueCollection());
    }

    public function testShouldCreateQueueUsingQueueNameFromContext()
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())
            ->method('debug')
            ->with('Make sure the queue "theQueueName" exists on a broker side.');

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setQueueName('theQueueName');
        $context->setLogger($logger);

        $this->driver->expects($this->once())
            ->method('createQueue')
            ->with('theQueueName')
            ->willReturn($this->createMock(QueueInterface::class));

        $this->extension->onBeforeReceive($context);
    }

    public function testShouldCreateSameQueueOnlyOnce()
    {
        $this->driver->expects($this->exactly(2))
            ->method('createQueue')
            ->withConsecutive(
                ['theQueueName1'],
                ['theQueueName2']
            )
            ->willReturnOnConsecutiveCalls(
                $this->createMock(QueueInterface::class),
                $this->createMock(QueueInterface::class)
            );

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->exactly(2))
            ->method('debug')
            ->withConsecutive(
                ['Make sure the queue "theQueueName1" exists on a broker side.'],
                ['Make sure the queue "theQueueName2" exists on a broker side.']
            );

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);
        $context->setQueueName('theQueueName1');

        $this->extension->onBeforeReceive($context);
        $this->extension->onBeforeReceive($context);

        $context = new Context($this->createMock(SessionInterface::class));
        $context->setLogger($logger);
        $context->setQueueName('theQueueName2');

        $this->extension->onBeforeReceive($context);
        $this->extension->onBeforeReceive($context);
    }
}
