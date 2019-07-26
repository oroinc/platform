<?php

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Component\Console\Input\InputArgument;

/**
 * Displays current "conditions" for an application
 */
class DebugConditionCommand extends AbstractDebugCommand
{
    public const ARGUMENT_NAME = 'condition-name';

    /** @var string */
    protected static $defaultName = 'oro:debug:condition';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Displays current "conditions" for an application')
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
    protected function getArgumentName(): string
    {
        return self::ARGUMENT_NAME;
    }
}
