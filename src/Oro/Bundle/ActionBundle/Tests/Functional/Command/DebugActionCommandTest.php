<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Command;

use Oro\Bundle\ActionBundle\Command\DebugActionCommand;

class DebugActionCommandTest extends AbstractDebugCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFactoryServiceId(): string
    {
        return 'oro_action.action_factory';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandName(): string
    {
        return DebugActionCommand::getDefaultName();
    }
}
