<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Command;

use Oro\Bundle\ActionBundle\Command\DebugConditionCommand;

class DebugConditionCommandTest extends AbstractDebugCommandTestCase
{
    /**
     * @return string
     */
    protected function getFactoryServiceId()
    {
        return DebugConditionCommand::FACTORY_SERVICE_ID;
    }

    /**
     * @return string
     */
    protected function getCommandName()
    {
        return DebugConditionCommand::COMMAND_NAME;
    }
}
