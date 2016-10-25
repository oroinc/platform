<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Command;

use Oro\Bundle\ActionBundle\Command\DebugActionCommand;

class DebugActionCommandTest extends AbstractDebugCommandTestCase
{
    public function testConfigure()
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getHelp());
        $this->assertEquals(DebugActionCommand::COMMAND_NAME, $this->command->getName());
    }

    /**
     * {@inheritdoc}
     */
    public function getFactoryServiceId()
    {
        return DebugActionCommand::FACTORY_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    public function getArgumentName()
    {
        return DebugActionCommand::ARGUMENT_NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getCommandInstance()
    {
        return new DebugActionCommand();
    }
}
