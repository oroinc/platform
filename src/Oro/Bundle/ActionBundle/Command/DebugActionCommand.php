<?php

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Component\Console\Input\InputArgument;

/**
 * Displays current "actions" for an application
 */
class DebugActionCommand extends AbstractDebugCommand
{
    public const ARGUMENT_NAME = 'action-name';

    /** @var string */
    protected static $defaultName = 'oro:debug:action';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Displays current "actions" for an application')
            ->addArgument(self::ARGUMENT_NAME, InputArgument::OPTIONAL, 'An "action" name')
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</info> displays the configured 'actions':

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
