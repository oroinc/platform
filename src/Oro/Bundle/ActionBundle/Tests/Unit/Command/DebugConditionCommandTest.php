<?php

namespace Oro\Bundle\ActionBundle\Tests\Unit\Command;

use Oro\Bundle\ActionBundle\Command\DebugConditionCommand;

class DebugConditionCommandTest extends AbstractDebugCommandTestCase
{
    public function testConfigure()
    {
        $this->assertNotEmpty($this->command->getDescription());
        $this->assertNotEmpty($this->command->getHelp());
        $this->assertEquals(DebugConditionCommand::COMMAND_NAME, $this->command->getName());
    }

    /**
     * {@inheritdoc}
     */
    protected function getFactoryServiceId()
    {
        return DebugConditionCommand::FACTORY_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getArgumentName()
    {
        return DebugConditionCommand::ARGUMENT_NAME;
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandInstance()
    {
        return new DebugConditionCommand();
    }
}
