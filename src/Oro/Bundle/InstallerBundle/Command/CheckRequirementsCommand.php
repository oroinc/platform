<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Requirements\Requirement;

/**
 * Checks that the environment meets the application requirements.
 */
class CheckRequirementsCommand extends Command
{
    protected const CATEGORY_MANDATORY_REQUIREMENTS = 'mandatory-requirements';
    protected const CATEGORY_ORO_REQUIREMENTS = 'oro-requirements';
    protected const CATEGORY_PHP_CONFIG_REQUIREMENTS = 'php-config-requirements';
    protected const CATEGORY_RECOMMENDATIONS = 'recommendations';

    protected const CATEGORIES = [
        self::CATEGORY_MANDATORY_REQUIREMENTS => 'Mandatory requirements',
        self::CATEGORY_PHP_CONFIG_REQUIREMENTS => 'PHP settings',
        self::CATEGORY_ORO_REQUIREMENTS => 'Oro specific requirements',
        self::CATEGORY_RECOMMENDATIONS => 'Optional recommendations'
    ];

    protected static $defaultName = 'oro:check-requirements';

    protected iterable $providersIterator;

    public function __construct(iterable $providersIterator)
    {
        $this->providersIterator = $providersIterator;

        parent::__construct();
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Checks that the environment meets the application requirements.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command checks that the environment meets the application requirements.

  <info>php %command.full_name%</info>

By default this command shows only errors, but you can increase the verbosity
to see warnings and informational messages as well:

  <info>php %command.full_name% -v</info>
  <info>php %command.full_name% -vv</info>

The command will return 0 on exit if all application requirements are met
and it will return 1 if some of the requirements are not fulfilled.

HELP
            )
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Check system requirements');

        $collections = $this->getAllCollections();
        $failedCount = 0;

        foreach (self::CATEGORIES as $category => $label) {
            $collection = $this->extractRequirementsByCategory($category, $collections);
            if ($category !== self::CATEGORY_RECOMMENDATIONS) {
                $failedCount += count($collection['failed']);
            }

            $this->renderTable($collection['all'], $label, $output);
        }

        $exitCode = $failedCount > 0 ? 1 : 0;
        $resultMessage = $this->getResultMessage($failedCount);

        $output->writeln($resultMessage);

        return $exitCode;
    }

    /**
     * @param Requirement[]  $requirements
     * @param string          $header
     * @param OutputInterface $output
     */
    protected function renderTable(array $requirements, string $header, OutputInterface $output): void
    {
        $rows = [];
        $verbosity = $output->getVerbosity();
        foreach ($requirements as $requirement) {
            if ($requirement->isFulfilled()) {
                if ($verbosity >= OutputInterface::VERBOSITY_VERY_VERBOSE) {
                    $rows[] = ['OK', $requirement->getTestMessage()];
                }
            } elseif ($requirement->isOptional()) {
                if ($verbosity >= OutputInterface::VERBOSITY_VERBOSE) {
                    $rows[] = ['WARNING', $requirement->getHelpText()];
                }
            } else {
                if ($verbosity >= OutputInterface::VERBOSITY_NORMAL) {
                    $rows[] = ['ERROR', $requirement->getHelpText()];
                }
            }
        }

        if (!empty($rows)) {
            $table = new Table($output);
            $table
                ->setHeaders(['Check  ', $header])
                ->setRows([]);
            foreach ($rows as $row) {
                $table->addRow($row);
            }
            $table->render();
        }
    }

    private function getResultMessage(int $failedCount): string
    {
        switch (true) {
            case $failedCount === 1:
                return '<error>Found 1 not fulfilled requirement</error>';
            case $failedCount > 1:
                return sprintf('<error>Found %d not fulfilled requirements</error>', $failedCount);
            default:
                return '<info>The application meets all mandatory requirements</info>';
        }
    }

    private function extractRequirementsByCategory(string $category, array $collections): array
    {
        $allRequirements = array_map(
            fn ($collection) => $collection->all(),
            $collections[$category] ?? []
        );
        $failedRequirements = array_map(
            fn ($collection) => $collection->getFailedRequirements(),
            $collections[$category] ?? []
        );

        return [
            'all' => array_merge(...$allRequirements),
            'failed' => array_merge(...$failedRequirements)
        ];
    }

    private function getAllCollections(): array
    {
        $collections = [];

        foreach ($this->providersIterator as $provider) {
            $collections[self::CATEGORY_MANDATORY_REQUIREMENTS][] = $provider->getMandatoryRequirements();
            $collections[self::CATEGORY_ORO_REQUIREMENTS][] = $provider->getOroRequirements();
            $collections[self::CATEGORY_PHP_CONFIG_REQUIREMENTS][] = $provider->getPhpIniRequirements();
            $collections[self::CATEGORY_RECOMMENDATIONS][] = $provider->getRecommendations();
        }

        return array_map('array_filter', $collections);
    }
}
