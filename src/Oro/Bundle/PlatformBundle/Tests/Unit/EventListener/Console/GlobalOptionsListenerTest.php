<?php

namespace Oro\Bundle\PlatformBundle\Tests\Unit\EventListener\Console;

use Oro\Bundle\PlatformBundle\EventListener\Console\GlobalOptionsListener;
use Oro\Bundle\PlatformBundle\Provider\Console\GlobalOptionsProviderRegistry;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Event\ConsoleCommandEvent;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GlobalOptionsListenerTest extends TestCase
{
    private GlobalOptionsProviderRegistry&MockObject $registry;
    private GlobalOptionsListener $listener;

    #[\Override]
    protected function setUp(): void
    {
        $this->registry = $this->createMock(GlobalOptionsProviderRegistry::class);
        $this->listener = new GlobalOptionsListener($this->registry);
    }

    public function testOnConsoleCommand(): void
    {
        $command = $this->createMock(Command::class);
        $input = $this->createMock(InputInterface::class);
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
