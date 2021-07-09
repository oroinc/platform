<?php

declare(strict_types=1);

namespace Oro\Bundle\InstallerBundle\Command;

use Oro\Bundle\InstallerBundle\Provider\PlatformRequirementsProvider;
use Oro\Bundle\InstallerBundle\Symfony\Requirements\Requirement;
use Oro\Bundle\InstallerBundle\Symfony\Requirements\RequirementCollection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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

    private string $projectDir;

    protected iterable $providersIterator;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;

        parent::__construct();
    }

    public function setProvidersIterator(iterable $providersIterator): void
    {
        $this->providersIterator = $providersIterator;
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

        $collections = $this->getAllCollections($input);
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
     * @param InputInterface $input
     *
     * @return \OroRequirements
     */
    protected function getRequirements(InputInterface $input)
    {
        if (!class_exists('OroRequirements')) {
            require_once $this->projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'OroRequirements.php';
        }

        return new \OroRequirements($input->getOption('env'));
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

    private function getAllCollections(InputInterface $input): array
    {
        $collections = [];
        $oldFilePath = $this->projectDir . DIRECTORY_SEPARATOR . 'var' . DIRECTORY_SEPARATOR . 'OroRequirements.php';

        foreach ($this->providersIterator as $provider) {
            if ($provider instanceof PlatformRequirementsProvider && file_exists($oldFilePath)) {
                $provider = $this->getRequirements($input);

                $collections[self::CATEGORY_MANDATORY_REQUIREMENTS][] = $this->oldRequirementsToCollection(
                    $provider->getMandatoryRequirements()
                );
                $collections[self::CATEGORY_ORO_REQUIREMENTS][] = $this->oldRequirementsToCollection(
                    $provider->getOroRequirements()
                );
                $collections[self::CATEGORY_PHP_CONFIG_REQUIREMENTS][] = $this->oldRequirementsToCollection(
                    $provider->getPhpIniRequirements()
                );
                $collections[self::CATEGORY_RECOMMENDATIONS][] = $this->oldRequirementsToCollection(
                    $provider->getRecommendations()
                );
            } else {
                $collections[self::CATEGORY_MANDATORY_REQUIREMENTS][] = $provider->getMandatoryRequirements();
                $collections[self::CATEGORY_ORO_REQUIREMENTS][] = $provider->getOroRequirements();
                $collections[self::CATEGORY_PHP_CONFIG_REQUIREMENTS][] = $provider->getPhpIniRequirements();
                $collections[self::CATEGORY_RECOMMENDATIONS][] = $provider->getRecommendations();
            }
        }

        return array_map('array_filter', $collections);
    }

    private function oldRequirementsToCollection(array $requirements): RequirementCollection
    {
        $collection = new RequirementCollection();

        foreach ($requirements as $requirement) {
            $collection->add(new Requirement(
                $requirement->isFulfilled(),
                $requirement->getTestMessage(),
                $requirement->getHelpHtml(),
                $requirement->getHelpText(),
                $requirement->isOptional()
            ));
        }

        return $collection;
    }
}
