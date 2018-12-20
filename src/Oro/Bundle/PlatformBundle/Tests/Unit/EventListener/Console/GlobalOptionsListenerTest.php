<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\Console;

use Oro\Bundle\PlatformBundle\EventListener\Console\GlobalOptionsListener;
use Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GlobalOptionsListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GlobalOptionsProviderRegistry|\PHPUnit\Framework\MockObject\MockObject
     */
    private $registry;

    /**
     * @var GlobalOptionsListener
     */
    private $listener;

    protected function setUp()
    {
        $this->registry = $this->createMock(GlobalOptionsProviderRegistry::class);
        $this->listener = new GlobalOptionsListener($this->registry);
    }

    public function testOnConsoleCommand()
    {
        /** @var Command|\PHPUnit\Framework\MockObject\MockObject $command */
        $command = $this->createMock(Command::class);
        /** @var InputInterface|\PHPUnit\Framework\MockObject\MockObject $input */
        $input = $this->createMock(InputInterface::class);
        /** @var OutputInterface $output */
        $output = $this->createMock(OutputInterface::class);
        $this->registry->expects($this->once())
            ->method('addGlobalOptions')
            ->with($command);
        $this->registry->expects($this->once())
            ->method('resolveGlobalOptions')
            ->with($input);
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
