<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Command;

use Oro\Bundle\ActionBundle\Command\DebugConditionCommand;
use Oro\Component\ConfigExpression\FactoryWithTypesInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;

class DebugConditionCommandTest extends AbstractDebugCommandTestCase
{
    public function testConfigure(): void
    {
        self::assertNotEmpty($this->command->getDescription());
        self::assertNotEmpty($this->command->getHelp());
        self::assertEquals(DebugConditionCommand::getDefaultName(), $this->command->getName());
    }

    #[\Override]
    protected function getArgumentName(): string
    {
        return DebugConditionCommand::ARGUMENT_NAME;
    }

    #[\Override]
    protected function getCommandInstance(ContainerInterface $container, FactoryWithTypesInterface $factory): Command
    {
        return new DebugConditionCommand($container, $factory);
    }
}
