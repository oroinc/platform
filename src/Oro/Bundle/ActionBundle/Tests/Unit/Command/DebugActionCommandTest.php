<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Command;

use Oro\Bundle\ActionBundle\Command\DebugActionCommand;
use Oro\Component\ConfigExpression\FactoryWithTypesInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DebugActionCommandTest extends AbstractDebugCommandTestCase
{
    public function testConfigure(): void
    {
        self::assertNotEmpty($this->command->getDescription());
        self::assertNotEmpty($this->command->getHelp());
        self::assertEquals(DebugActionCommand::getDefaultName(), $this->command->getName());
    }

    #[\Override]
    protected function getArgumentName(): string
    {
        return DebugActionCommand::ARGUMENT_NAME;
    }

    #[\Override]
    protected function getCommandInstance(ContainerInterface $container, FactoryWithTypesInterface $factory): Command
    {
        return new DebugActionCommand($container, $factory);
    }
}
