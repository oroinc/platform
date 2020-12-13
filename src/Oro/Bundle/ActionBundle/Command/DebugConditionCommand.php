<?php
declare(strict_types=1);

namespace Oro\Bundle\ActionBundle\Command;

use Symfony\Component\Console\Input\InputArgument;

/**
 * Displays available conditions.
 */
class DebugConditionCommand extends AbstractDebugCommand
{
    public const ARGUMENT_NAME = 'condition-name';

    /** @var string */
    protected static $defaultName = 'oro:debug:condition';

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addArgument(self::ARGUMENT_NAME, InputArgument::OPTIONAL, 'Condition name')
            ->setDescription('Displays available conditions.')
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

    protected function getArgumentName(): string
    {
        return self::ARGUMENT_NAME;
    }
}
