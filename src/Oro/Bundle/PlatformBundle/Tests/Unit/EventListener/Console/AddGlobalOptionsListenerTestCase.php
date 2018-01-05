<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\Console;

use Symfony\Component\Console\Application;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class AddGlobalOptionsListenerTestCase extends \PHPUnit_Framework_TestCase
{
    abstract public function testOnConsoleCommand();

    /**
     * @return ConsoleCommandEvent
     */
    protected function getEvent()
    {
        $inputDefinition = new InputDefinition();
        $application = $this->createMock(Application::class);
        $application->expects($this->any())
            ->method('getDefinition')
            ->willReturn($inputDefinition);
        $application->expects($this->once())
            ->method('getHelperSet')
            ->willReturn(new HelperSet());

        $commandDefinition = new InputDefinition();
        $command = new Command('test');
        $command->setApplication($application);
        $command->setDefinition($commandDefinition);

        /**
         * @var InputInterface $input
         * @var OutputInterface $output
         */
        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        return new ConsoleCommandEvent($command, $input, $output);
    }
}
