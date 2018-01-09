<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\Console;

use Oro\Bundle\PlatformBundle\EventListener\Console\RebindDefinitionListener;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RebindDefinitionListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var RebindDefinitionListener
     */
    private $listener;

    protected function setUp()
    {
        $this->listener = new RebindDefinitionListener();
    }

    public function testOnConsoleCommand()
    {
        /**
         * @var Command|\PHPUnit_Framework_MockObject_MockObject $command
         * @var InputInterface|\PHPUnit_Framework_MockObject_MockObject $input
         * @var OutputInterface|\PHPUnit_Framework_MockObject_MockObject $output
         */
        $command = $this->createMock(Command::class);
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        $definition = new InputDefinition();
        $command->expects($this->once())
            ->method('mergeApplicationDefinition');
        $command->expects($this->once())
            ->method('getDefinition')
            ->willReturn($definition);
        $input->expects($this->once())
            ->method('bind')
            ->with($definition);

        $event = new ConsoleCommandEvent($command, $input, $output);
        $this->listener->onConsoleCommand($event);
    }
}
