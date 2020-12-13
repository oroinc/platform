<?php
declare(strict_types=1);

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Component\Console\Input\InputArgument;

/**
 * Displays available actions.
 */
class DebugActionCommand extends AbstractDebugCommand
{
    public const ARGUMENT_NAME = 'action-name';

    /** @var string */
    protected static $defaultName = 'oro:debug:action';

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument(self::ARGUMENT_NAME, InputArgument::OPTIONAL, 'Action name')
            ->setDescription('Displays available actions.')
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

    protected function getArgumentName(): string
    {
        return self::ARGUMENT_NAME;
    }
}
