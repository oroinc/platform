<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Consumption;

use Oro\Component\MessageQueue\Consumption\ChainExtension;
use Oro\Component\MessageQueue\Consumption\ConsumeMessagesCommand;
use Oro\Component\MessageQueue\Consumption\MessageProcessorInterface;
use Oro\Component\MessageQueue\Consumption\QueueConsumer;
use Oro\Component\MessageQueue\Transport\ConnectionInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Tester\CommandTester;

class ConsumeMessagesCommandTest extends \PHPUnit\Framework\TestCase
{
    /** @var ConsumeMessagesCommand */
    private $command;

    /** @var QueueConsumer|\PHPUnit\Framework\MockObject\MockObject */
    private $consumer;

    /** @var ContainerInterface|\PHPUnit\Framework\MockObject\MockObject */
    private $processorLocator;

    protected function setUp()
    {
        $this->consumer = $this->createMock(QueueConsumer::class);
        $this->processorLocator = $this->createMock(ContainerInterface::class);

        $this->command = new ConsumeMessagesCommand($this->consumer, $this->processorLocator);
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

        $this->processorLocator->expects($this->once())
            ->method('get')
            ->with('processor-service')
            ->willReturn(new \stdClass());

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

        $this->processorLocator->expects($this->once())
            ->method('get')
            ->with('processor-service')
            ->willReturn($processor);

        $tester = new CommandTester($this->command);
        $tester->execute([
            'queue' => 'queue-name',
            'processor-service' => 'processor-service'
        ]);
    }
}
