<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Client\DelegateMessageProcessor;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\MessageQueue\Consumption\Extensions;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ConsumeMessagesCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateMessageProcessorMock(),
            $this->createDestinationMetaRegistry([])
        );
    }

    public function testShouldHaveCommandName()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateMessageProcessorMock(),
            $this->createDestinationMetaRegistry([])
        );

        $this->assertEquals('oro:message-queue:consume', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateMessageProcessorMock(),
            $this->createDestinationMetaRegistry([])
        );

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(3, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateMessageProcessorMock(),
            $this->createDestinationMetaRegistry([])
        );

        $arguments = $command->getDefinition()->getArguments();

        $this->assertCount(1, $arguments);
        $this->assertArrayHasKey('clientDestinationName', $arguments);
    }

    public function testShouldExecuteConsumptionAndUseDefaultQueueName()
    {
        $processor = $this->createDelegateMessageProcessorMock();

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('close')
        ;

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('bind')
            ->with('aprefixt.adefaultqueuename', $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(Extensions::class))
        ;
        $consumer
            ->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection))
        ;

        $destinationMetaRegistry = $this->createDestinationMetaRegistry([
            'default' => [],
        ]);

        $command = new ConsumeMessagesCommand($consumer, $processor, $destinationMetaRegistry);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldExecuteConsumptionAndUseCustomClientDestinationName()
    {
        $processor = $this->createDelegateMessageProcessorMock();

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('close')
        ;

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('bind')
            ->with('aprefixt.non-default-queue', $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(Extensions::class))
        ;
        $consumer
            ->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection))
        ;

        $destinationMetaRegistry = $this->createDestinationMetaRegistry([
            'non-default-queue' => []
        ]);

        $command = new ConsumeMessagesCommand($consumer, $processor, $destinationMetaRegistry);

        $tester = new CommandTester($command);
        $tester->execute([
            'clientDestinationName' => 'non-default-queue'
        ]);
    }

    public function testShouldExecuteConsumptionAndUseCustomClientDestinationNameWithCustomQueueFromArgument()
    {
        $processor = $this->createDelegateMessageProcessorMock();

        $connection = $this->createConnectionMock();
        $connection
            ->expects($this->once())
            ->method('close')
        ;

        $consumer = $this->createQueueConsumerMock();
        $consumer
            ->expects($this->once())
            ->method('bind')
            ->with('non-default-transport-queue', $this->identicalTo($processor))
        ;
        $consumer
            ->expects($this->once())
            ->method('consume')
            ->with($this->isInstanceOf(Extensions::class))
        ;
        $consumer
            ->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($connection))
        ;

        $destinationMetaRegistry = $this->createDestinationMetaRegistry([
            'default' => [],
            'non-default-queue' => ['transportName' => 'non-default-transport-queue']
        ]);

        $command = new ConsumeMessagesCommand($consumer, $processor, $destinationMetaRegistry);

        $tester = new CommandTester($command);
        $tester->execute([
            'clientDestinationName' => 'non-default-queue'
        ]);
    }

    /**
     * @param array $destinationNames
     *
     * @return DestinationMetaRegistry
     */
    protected function createDestinationMetaRegistry(array $destinationNames)
    {
        $config = new Config('aPrefixt', 'aRouterMessageProcessorName', 'aRouterQueueName', 'aDefaultQueueName');

        return new DestinationMetaRegistry($config, $destinationNames, 'default');
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConnectionInterface
     */
    protected function createConnectionMock()
    {
        return $this->getMock(ConnectionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DelegateMessageProcessor
     */
    protected function createDelegateMessageProcessorMock()
    {
        return $this->getMock(DelegateMessageProcessor::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueConsumer
     */
    protected function createQueueConsumerMock()
    {
        return $this->getMock(QueueConsumer::class, [], [], '', false);
    }
}
