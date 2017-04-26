<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

use Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;

class ConsumeMessagesCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var ConsumeMessagesCommand */
    private $command;

    /** @var Container */
    private $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $consumer;

    protected function setUp()
    {
        $this->consumer = $this->createMock(QueueConsumer::class);

        $this->command = new ConsumeMessagesCommand();

        $this->container = new Container();
        $this->container->set('oro_message_queue.consumption.queue_consumer', $this->consumer);
        $this->command->setContainer($this->container);
    }

    public function testShouldHaveCommandName()
    {
        $this->assertEquals('oro:message-queue:transport:consume', $this->command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $options = $this->command->getDefinition()->getOptions();

        $this->assertCount(3, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $arguments = $this->command->getDefinition()->getArguments();

        $this->assertCount(2, $arguments);
        $this->assertArrayHasKey('processor-service', $arguments);
        $this->assertArrayHasKey('queue', $arguments);
    }

    public function testShouldThrowExceptionIfProcessorInstanceHasWrongClass()
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('Invalid message processor service given.'.
            ' It must be an instance of Oro\Component\MessageQueue\Consumption\MessageProcessorInterface but stdClass');

        $this->container->set('processor-service', new \stdClass());

        $tester = new CommandTester($this->command);
        $tester->execute([
            'queue' => 'queue-name',
            'processor-service' => 'processor-service'
        ]);
    }

    public function testShouldExecuteConsumption()
    {
        $processor = $this->createMock(MessageProcessorInterface::class);

        $connection = $this->createMock(ConnectionInterface::class);
        $connection->expects($this->once())
            ->method('close');

        $this->consumer->expects($this->once())
            ->method('bind')
            ->with('queue-name', $this->identicalTo($processor));
        $this->consumer->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(ChainExtension::class));
        $this->consumer->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection));

        $this->container->set('processor-service', $processor);

        $tester = new CommandTester($this->command);
        $tester->execute([
            'queue' => 'queue-name',
            'processor-service' => 'processor-service'
        ]);
    }
}
