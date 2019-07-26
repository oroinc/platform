<?php

namespace Oro\Bundle\ActionBundle\Tests\Functional\Command;

use Oro\Bundle\ActionBundle\Command\DebugConditionCommand;

class DebugConditionCommandTest extends AbstractDebugCommandTestCase
{
    /**
     * {@inheritdoc}
     */
    protected function getFactoryServiceId(): string
    {
        return 'oro_action.expression.factory';
    }

    /**
     * {@inheritdoc}
     */
    protected function getCommandName(): string
    {
        return DebugConditionCommand::getDefaultName();
    }
}
