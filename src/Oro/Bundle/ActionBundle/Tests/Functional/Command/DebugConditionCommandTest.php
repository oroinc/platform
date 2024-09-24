<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Command;

use Oro\Bundle\ActionBundle\Command\DebugConditionCommand;

class DebugConditionCommandTest extends AbstractDebugCommandTestCase
{
    #[\Override]
    protected function getFactoryServiceId(): string
    {
        return 'oro_action.expression.factory';
    }

    #[\Override]
    protected function getCommandName(): string
    {
        return DebugConditionCommand::getDefaultName();
    }
}
