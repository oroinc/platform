<?php

declare(strict_types=1);

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;

/**
 * Displays available conditions.
 */
#[AsCommand(
    name: 'oro:debug:condition',
    description: 'Displays available conditions.'
)]
class DebugConditionCommand extends AbstractDebugCommand
{
    public const ARGUMENT_NAME = 'condition-name';

    /** @noinspection PhpMissingParentCallCommonInspection */
    #[\Override]
    protected function configure()
    {
        $this
            ->addArgument(self::ARGUMENT_NAME, InputArgument::OPTIONAL, 'Condition name')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command displays available conditions.

  <info>php %command.full_name%</info>

To get information about a specific condition, specify its name:

  <info>php %command.full_name% <condition></info>
  <info>php %command.full_name% instanceof</info>

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
