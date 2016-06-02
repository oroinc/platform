<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\Extensions;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

class ConsumeMessagesCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new ConsumeMessagesCommand($this->createQueueConsumerMock());
    }

    public function testShouldHaveCommandName()
    {
        $command = new ConsumeMessagesCommand($this->createQueueConsumerMock());

        $this->assertEquals('oro:message-queue:transport:consume', $command->getName());
    }

    public function testShouldHaveExpectedOptions()
    {
        $command = new ConsumeMessagesCommand($this->createQueueConsumerMock());

        $options = $command->getDefinition()->getOptions();

        $this->assertCount(3, $options);
        $this->assertArrayHasKey('memory-limit', $options);
        $this->assertArrayHasKey('message-limit', $options);
        $this->assertArrayHasKey('time-limit', $options);
    }

    public function testShouldHaveExpectedAttributes()
    {
        $command = new ConsumeMessagesCommand($this->createQueueConsumerMock());

        $arguments = $command->getDefinition()->getArguments();

        $this->assertCount(2, $arguments);
        $this->assertArrayHasKey('processor-service', $arguments);
        $this->assertArrayHasKey('queue', $arguments);
    }

    public function testShouldThrowExceptionIfProcessorInstanceHasWrongClass()
    {
        $this->setExpectedException(\LogicException::class, 'Invalid message processor service given.'.
            ' It must be an instance of Oro\Component\MessageQueue\Consumption\MessageProcessorInterface but stdClass');

        $container = new Container();
        $container->set('processor-service', new \stdClass());

        $command = new ConsumeMessagesCommand($this->createQueueConsumerMock());
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([
            'queue' => 'queue-name',
            'processor-service' => 'processor-service'
        ]);
    }

    public function testShouldExecuteConsumption()
    {
        $processor = $this->createMessageProcessor();

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

        $container = new Container();
        $container->set('processor-service', $processor);

        $command = new ConsumeMessagesCommand($consumer);
        $command->setContainer($container);

        $tester = new CommandTester($command);
        $tester->execute([
            'queue' => 'queue-name',
            'processor-service' => 'processor-service'
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
     * @return \PHPUnit_Framework_MockObject_MockObject|MessageProcessorInterface
     */
    protected function createMessageProcessor()
    {
        return $this->getMock(MessageProcessorInterface::class);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|QueueConsumer
     */
    protected function createQueueConsumerMock()
    {
        return $this->getMock(QueueConsumer::class, [], [], '', false);
    }
}
