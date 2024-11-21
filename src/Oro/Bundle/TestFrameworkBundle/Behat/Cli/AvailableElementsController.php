<?php

namespace Oro\Bundle\TestFrameworkBundle\Behat\Cli;

use Behat\Testwork\Cli\Controller;
use Oro\Bundle\TestFrameworkBundle\Behat\Cli\Descriptor\MarkdownDescriptor;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Dump to console output available behat elements in *.md format.
 */
class AvailableElementsController implements Controller
{
    public function __construct(private array $elements)
    {
    }

    #[\Override]
    public function configure(SymfonyCommand $command)
    {
        $command
            ->addOption(
                '--elements',
                null,
                InputOption::VALUE_NONE,
                'Show all available test elements.'.PHP_EOL.
                'Elements can be configured by configuration behat.yml'
            )->addOption(
                '--write-format',
                null,
                InputOption::VALUE_OPTIONAL,
                'Works with --elements and --contexts options. Supported [list, table].'
            );
    }

    #[\Override]
    public function execute(InputInterface $input, OutputInterface $output)
    {
        if (!$input->getOption('elements')) {
            return;
        }

        $descriptor = new MarkdownDescriptor();
        $elements = [];
        foreach ($this->elements as $elementName => $element) {
            $elements[] = [
                $elementName,
                [
                    'type' => 'code',
                    'code' => $element['selector']['type'] ?? '',
                    'value' => $element['selector']['locator'] ?? ''
                ],
            ];
        }

        $descriptor->describe(
            $output,
            $elements,
            [
                'format' => $input->getOption('write-format') ? $input->getOption('write-format') : 'list',
                'tableName' => 'Available test elements',
                'headers' => ['Name', 'Selector'],
            ]
        );

        return 0;
    }
}
