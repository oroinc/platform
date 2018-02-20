<?php

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Component\Console\Input\InputArgument;

class DebugConditionCommand extends AbstractDebugCommand
{
    const COMMAND_NAME = 'oro:debug:condition';
    const ARGUMENT_NAME = 'condition-name';
    const FACTORY_SERVICE_ID = 'oro_action.expression.factory';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription('Displays current "conditions" for an application')
            ->addArgument(self::ARGUMENT_NAME, InputArgument::OPTIONAL, 'A condition name')
            ->setHelp(
                <<<EOF
The <info>%command.name%</info> displays list of all conditions with full description:

  <info>php %command.full_name%</info>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function getFactoryServiceId()
    {
        return self::FACTORY_SERVICE_ID;
    }

    /**
     * {@inheritdoc}
     */
    protected function getArgumentName()
    {
        return self::ARGUMENT_NAME;
    }
}
