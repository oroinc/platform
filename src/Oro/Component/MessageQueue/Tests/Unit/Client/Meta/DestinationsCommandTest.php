<?php

namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\Container;

use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationsCommand;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;

class DestinationsCommandTest extends \PHPUnit_Framework_TestCase
{
    /** @var DestinationsCommand */
    private $command;

    /** @var Container */
    private $container;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $registry;

    protected function setUp()
    {
        $this->registry = $this->createMock(DestinationMetaRegistry::class);

        $this->command = new DestinationsCommand();

        $this->container = new Container();
        $this->container->set('oro_message_queue.client.meta.destination_meta_registry', $this->registry);
        $this->command->setContainer($this->container);
    }

    public function testShouldHaveCommandName()
    {
        $this->assertEquals('oro:message-queue:destinations', $this->command->getName());
    }

    public function testShouldShowMessageFoundZeroDestinationsIfAnythingInRegistry()
    {
        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([]);

        $output = $this->executeCommand();

        $this->assertContains('Found 0 destinations', $output);
    }

    public function testShouldShowMessageFoundTwoDestinations()
    {
        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([
                new DestinationMeta('aClientName', 'aDestinationName'),
                new DestinationMeta('anotherClientName', 'anotherDestinationName')
            ]);

        $output = $this->executeCommand();

        $this->assertContains('Found 2 destinations', $output);
    }

    public function testShouldShowInfoAboutDestinations()
    {
        $this->registry->expects(self::once())
            ->method('getDestinationsMeta')
            ->willReturn([
                new DestinationMeta('aFooClientName', 'aFooDestinationName', ['fooSubscriber']),
                new DestinationMeta('aBarClientName', 'aBarDestinationName', ['barSubscriber']),
            ]);

        $output = $this->executeCommand();

        $this->assertContains('aFooClientName', $output);
        $this->assertContains('aFooDestinationName', $output);
        $this->assertContains('fooSubscriber', $output);
        $this->assertContains('aBarClientName', $output);
        $this->assertContains('aBarDestinationName', $output);
        $this->assertContains('barSubscriber', $output);
    }

    /**
     * @param string[] $arguments
     *
     * @return string
     */
    protected function executeCommand(array $arguments = [])
    {
        $tester = new CommandTester($this->command);
        $tester->execute($arguments);

        return $tester->getDisplay();
    }
}
