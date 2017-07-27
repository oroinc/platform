<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

use Oro\Component\MessageQueue\Client\CreateQueuesCommand;
use Oro\Component\MessageQueue\Client\DriverInterface;
use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;

class CreateQueuesCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var CreateQueuesCommand */
    private $command;

    /** @var Container */
    private $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $driver;

    protected function setUp()
    {
        $this->registry = $this->createMock(DestinationMetaRegistry::class);
        $this->driver = $this->createMock(DriverInterface::class);

        $this->command = new CreateQueuesCommand();

        $this->container = new Container();
        $this->container->set('oro_message_queue.client.meta.destination_meta_registry', $this->registry);
        $this->container->set('oro_message_queue.client.driver', $this->driver);
        $this->command->setContainer($this->container);
    }

    public function testShouldHaveCommandName()
    {
        $this->assertEquals('oro:message-queue:create-queues', $this->command->getName());
    }

    public function testShouldCreateQueues()
    {
        $destinationMeta1 = new DestinationMeta('', 'queue1');
        $destinationMeta2 = new DestinationMeta('', 'queue2');

        $this->registry->expects($this->once())
            ->method('getDestinationsMeta')
            ->will($this->returnValue([$destinationMeta1, $destinationMeta2]));

        $this->driver->expects($this->at(0))
            ->method('createQueue')
            ->with('queue1');
        $this->driver->expects($this->at(1))
            ->method('createQueue')
            ->with('queue2');

        $tester = new CommandTester($this->command);
        $tester->execute([]);

        $this->assertContains('Creating queue: queue1', $tester->getDisplay());
        $this->assertContains('Creating queue: queue2', $tester->getDisplay());
    }
}
