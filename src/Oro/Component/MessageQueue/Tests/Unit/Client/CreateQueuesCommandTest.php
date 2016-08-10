<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Oro\Component\MessageQueue\Client\CreateQueuesCommand;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Symfony\Component\Console\Tester\CommandTester;

class CreateQueuesCommandTest extends \PHPUnit_Framework_TestCase
{
    public function testCouldBeConstructedWithRequiredAttributes()
    {
        new CreateQueuesCommand($this->createDestinationMetaRegistryMock(), $this->createClientDriverMock());
    }

    public function testShouldHaveCommandName()
    {
        $command = new CreateQueuesCommand($this->createDestinationMetaRegistryMock(), $this->createClientDriverMock());

        $this->assertEquals('oro:message-queue:create-queues', $command->getName());
    }

    public function testShouldCreateQueues()
    {
        $destinationMeta1 = new DestinationMeta('queue1', '');
        $destinationMeta2 = new DestinationMeta('queue2', '');

        $destinationMetaRegistry = $this->createDestinationMetaRegistryMock();
        $destinationMetaRegistry
            ->expects($this->once())
            ->method('getDestinationsMeta')
            ->will($this->returnValue([$destinationMeta1, $destinationMeta2]))
        ;

        $driver = $this->createClientDriverMock();
        $driver
            ->expects($this->at(0))
            ->method('createQueue')
            ->with('queue1')
        ;
        $driver
            ->expects($this->at(1))
            ->method('createQueue')
            ->with('queue2')
        ;

        $command = new CreateQueuesCommand($destinationMetaRegistry, $driver);

        $tester = new CommandTester($command);
        $tester->execute([]);

        $this->assertContains('Creating queue: queue1', $tester->getDisplay());
        $this->assertContains('Creating queue: queue2', $tester->getDisplay());
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DestinationMetaRegistry
     */
    private function createDestinationMetaRegistryMock()
    {
        return $this->getMock(DestinationMetaRegistry::class, [], [], '', false);
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DriverInterface
     */
    private function createClientDriverMock()
    {
        return $this->getMock(DriverInterface::class);
    }
}
