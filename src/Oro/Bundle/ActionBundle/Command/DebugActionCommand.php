<?php

declare(strict_types=1);

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Displays available actions.
 */
#[AsCommand(
    name: 'oro:debug:action',
    description: 'Displays available actions.'
)]
class DebugActionCommand extends AbstractDebugCommand
{
    public const ARGUMENT_NAME = 'action-name';

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->addArgument(self::ARGUMENT_NAME, InputArgument::OPTIONAL, 'Action name')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays available actions.

  <info>php %command.full_name%</info>

To get information about a specific action, specify its name:

  <info>php %command.full_name% <action></info>
  <info>php %command.full_name% flash_message</info>

HELP
            )
        ;
    }

    #[\Override]
    protected function getArgumentName(): string
    {
        return self::ARGUMENT_NAME;
    }
}
