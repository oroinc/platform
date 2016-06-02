<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\Config;
use Oro\Component\MessageQueue\Client\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Client\DelegateMessageProcessor;
use Oro\Component\MessageQueue\Client\DriverInterface;
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
            $this->createDriverMock()
        );
    }

    public function testShouldHaveCommandName()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateMessageProcessorMock(),
            $this->createDriverMock()
        );

        $this->assertEquals('oro:message-queue:consume', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ConsumeMessagesCommand(
            $this->createQueueConsumerMock(),
            $this->createDelegateMessageProcessorMock(),
            $this->createDriverMock()
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
            $this->createDriverMock()
        );

        $arguments = $command->getDefinition()->getArguments();

        $this->assertCount(1, $arguments);
        $this->assertArrayHasKey('queue', $arguments);
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
            ->with('queue-name', $this->identicalTo($processor))
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

        $session = $this->createDriverMock();
        $session
            ->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue(new Config('', '', '', 'queue-name')))
        ;

        $command = new ConsumeMessagesCommand($consumer, $processor, $session);

        $tester = new CommandTester($command);
        $tester->execute([]);
    }

    public function testShouldExecuteConsumptionAndUseQueueFromArgument()
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
            ->with('non-default-queue', $this->identicalTo($processor))
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

        $session = $this->createDriverMock();
        $session
            ->expects($this->once())
            ->method('getConfig')
            ->will($this->returnValue(new Config('', '', '', 'queue-name')))
        ;

        $command = new ConsumeMessagesCommand($consumer, $processor, $session);

        $tester = new CommandTester($command);
        $tester->execute([
            'queue' => 'non-default-queue'
        ]);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|ConnectionInterface
     */
    protected function createConnectionMock()
    {
        return $this->getMock(ConnectionInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    protected function createDriverMock()
    {
        return $this->getMock(DriverInterface::class);
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
