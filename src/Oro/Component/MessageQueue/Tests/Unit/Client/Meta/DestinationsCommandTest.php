<?php
namespace Oro\Component\MessageQueue\Tests\Unit\Client\Meta;

use Oro\Component\MessageQueue\Client\Meta\DestinationMeta;
use Oro\Component\MessageQueue\Client\Meta\DestinationsCommand;
use Oro\Component\MessageQueue\Client\Meta\DestinationMetaRegistry;
use Oro\Component\Testing\ClassExtensionTrait;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

class DestinationsCommandTest extends \PHPUnit_Framework_TestCase
{
    use ClassExtensionTrait;
    
    public function testShouldBeSubClassOfCommand()
    {
        $this->assertClassExtends(Command::class, DestinationsCommand::class);
    }
    
    public function testCouldBeConstructedWithDestinationMetaRegistryAsFirstArgument()
    {
        new DestinationsCommand($this->createDestinationMetaRegistryStub());
    }

    public function testShouldShowMessageFoundZeroDestinationsIfAnythingInRegistry()
    {
        $command = new DestinationsCommand($this->createDestinationMetaRegistryStub());

        $output = $this->executeCommand($command);

        $this->assertContains('Found 0 destinations', $output);
    }

    public function testShouldShowMessageFoundTwoDestinations()
    {
        $command = new DestinationsCommand($this->createDestinationMetaRegistryStub([
            new DestinationMeta('aClientName', 'aDestinationName'),
            new DestinationMeta('anotherClientName', 'anotherDestinationName')
        ]));

        $output = $this->executeCommand($command);

        $this->assertContains('Found 2 destinations', $output);
    }

    public function testShouldShowInfoAboutDestinations()
    {
        $command = new DestinationsCommand($this->createDestinationMetaRegistryStub([
            new DestinationMeta('aFooClientName', 'aFooDestinationName', ['fooSubscriber']),
            new DestinationMeta('aBarClientName', 'aBarDestinationName', ['barSubscriber']),
        ]));

        $output = $this->executeCommand($command);

        $this->assertContains('aFooClientName', $output);
        $this->assertContains('aFooDestinationName', $output);
        $this->assertContains('fooSubscriber', $output);
        $this->assertContains('aBarClientName', $output);
        $this->assertContains('aBarDestinationName', $output);
        $this->assertContains('barSubscriber', $output);
    }

    /**
     * @param Command $command
     * @param string[] $arguments
     *
     * @return string
     */
    protected function executeCommand(Command $command, array $arguments = array())
    {
        $tester = new CommandTester($command);
        $tester->execute($arguments);

        return $tester->getDisplay();
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject|DestinationMetaRegistry
     */
    protected function createDestinationMetaRegistryStub($destinations = [])
    {
        $registryMock = $this->getMock(DestinationMetaRegistry::class, [], [], '', false);
        $registryMock
            ->expects($this->any())
            ->method('getDestinationsMeta')
            ->willReturn($destinations)
        ;

        return $registryMock;
    }
}
