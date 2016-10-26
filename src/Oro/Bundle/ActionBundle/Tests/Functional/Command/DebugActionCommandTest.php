<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Command;

use Oro\Bundle\ActionBundle\Command\DebugActionCommand;

class DebugActionCommandTest extends AbstractDebugCommandTestCase
{
    /**
     * @return string
     */
    protected function getFactoryServiceId()
    {
        return DebugActionCommand::FACTORY_SERVICE_ID;
    }

    /**
     * @return string
     */
    protected function getCommandName()
    {
        return DebugActionCommand::COMMAND_NAME;
    }
}
