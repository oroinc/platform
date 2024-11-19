<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Cli\Descriptor\MarkdownDescriptor;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dump to console output available behat contexts in *.md format.
 */
class AvailableContextsController implements Controller
{
    public function __construct(private array $contexts)
    {
    }

    #[\Override]
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--contexts',
                null,
                InputOption::VALUE_NONE,
                'Show all available test contexts.'.PHP_EOL.
                'Contexts can be configured automatically by extensions, and manually by configuration'
            )
            ->addOption(
                '--write-format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Works with --elements and --contexts options. Supported [list, table].'
            );
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('contexts')) {
            return;
        }

        $descriptor = new MarkdownDescriptor();

        $contexts = [];
        foreach ($this->contexts as $contextClass) {
            $contexts[] = [
                $contextClass
            ];
        }


        $descriptor->describe(
            $output,
            $contexts,
            [
                'format' => $input->getOption('write-format') ? $input->getOption('write-format') : 'list',
                'tableName' => 'Available test contexts',
                'headers' => ['Class'],
            ]
        );

        return 0;
    }
}
