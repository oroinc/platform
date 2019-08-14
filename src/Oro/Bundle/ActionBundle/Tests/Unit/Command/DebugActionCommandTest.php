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
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getHelp());
        $this->assertEquals(DebugActionCommand::getDefaultName(), $this->command->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getArgumentName(): string
    {
        return DebugActionCommand::ARGUMENT_NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandInstance(ContainerInterface $container, FactoryWithTypesInterface $factory): Command
    {
        return new DebugActionCommand($container, $factory);
    }
}
