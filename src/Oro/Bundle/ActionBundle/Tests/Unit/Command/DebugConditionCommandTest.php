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
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getHelp());
        $this->assertEquals(DebugConditionCommand::getDefaultName(), $this->command->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getArgumentName(): string
    {
        return DebugConditionCommand::ARGUMENT_NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandInstance(ContainerInterface $container, FactoryWithTypesInterface $factory): Command
    {
        return new DebugConditionCommand($container, $factory);
    }
}
