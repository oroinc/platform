<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Command;

class DebugActionCommandTest extends AbstractDebugCommandTestCase
{
    #[\Override]
    protected function getFactoryServiceId(): string
    {
        return 'oro_action.action_factory';
    }

    #[\Override]
    protected function getCommandName(): string
    {
        return 'oro:debug:action';
    }
}
