<?php
declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\Command;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\InstallerBundle\Command\Provider\InputOptionProvider;
use Oro\Bundle\LocaleBundle\Command\LocalizationOptionsCommandTrait;
use Oro\Bundle\LocaleBundle\DependencyInjection\Configuration;
use Oro\Bundle\LocaleBundle\DependencyInjection\OroLocaleExtension;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\MigrationBundle\Locator\FixturePathLocatorInterface;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutorInterface;
use Oro\Bundle\MigrationBundle\Migration\Loader\DataFixturesLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Cursor;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Loads data fixtures.
 */
class LoadDataFixturesCommand extends Command
{
    use LocalizationOptionsCommandTrait;

    public const MAIN_FIXTURES_TYPE = DataFixturesExecutorInterface::MAIN_FIXTURES;
    public const DEMO_FIXTURES_TYPE = DataFixturesExecutorInterface::DEMO_FIXTURES;

    /** @var string */
    protected static $defaultName = 'oro:migration:data:load';

    protected KernelInterface $kernel;
    protected DataFixturesLoader $dataFixturesLoader;
    protected DataFixturesExecutorInterface $dataFixturesExecutor;
    protected FixturePathLocatorInterface $fixturePathLocator;
    protected ConfigManager $configManager;
    protected ManagerRegistry $doctrine;

    private InputOptionProvider $inputOptionProvider;

    public function __construct(
        KernelInterface $kernel,
        DataFixturesLoader $dataFixturesLoader,
        DataFixturesExecutorInterface $dataFixturesExecutor,
        FixturePathLocatorInterface $fixturePathLocator,
        ConfigManager $configManager,
        ManagerRegistry $doctrine
    ) {
        parent::__construct();

        $this->kernel = $kernel;
        $this->dataFixturesLoader = $dataFixturesLoader;
        $this->dataFixturesExecutor = $dataFixturesExecutor;
        $this->fixturePathLocator = $fixturePathLocator;
        $this->configManager = $configManager;
        $this->doctrine = $doctrine;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->addOption(
                'fixtures-type',
                null,
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Fixtures type to be loaded (%s or %s). By default - %s',
                    self::MAIN_FIXTURES_TYPE,
                    self::DEMO_FIXTURES_TYPE,
                    self::MAIN_FIXTURES_TYPE
                ),
                self::MAIN_FIXTURES_TYPE
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the list of fixtures without applying them')
            ->addOption(
                'bundles',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Bundles to load the data from'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Bundles with the fixtures that should be skipped'
            )
            ->addLocalizationOptions()
            ->setDescription('Loads data fixtures.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command loads data fixtures.
The fixtures type ("main", or "demo") can be specified with the <info>--fixtures-type</info> option:

  <info>php %command.full_name% --fixtures-type=<type></info>

The <info>--dry-run</info> option can be used to print the list of fixtures without applying them:

  <info>php %command.full_name% --dry-run</info>

The <info>--bundles</info> option can be used to load the fixtures only from the specified bundles:

  <info>php %command.full_name% --bundles=<BundleOne> --bundles=<BundleTwo> --bundles=<BundleThree></info>

The <info>--exclude</info> option will skip loading fixtures from the specified bundles:

  <info>php %command.full_name% --exclude=<BundleOne> --exclude=<BundleTwo> --exclude=<BundleThree></info>

HELP
                . $this->getLocalizationOptionsHelp()
            )
            ->addUsage('--fixtures-type=main')
            ->addUsage('--fixtures-type=demo')
            ->addUsage('--dry-run')
            ->addUsage('--bundles=<BundleOne> --bundles=<BundleTwo>')
            ->addUsage('--fixtures-type=demo --bundles=<BundleOne> --bundles=<BundleTwo>')
            ->addUsage('--dry-run --fixtures-type=demo --bundles=<BundleOne> --bundles=<BundleTwo>')
            ->addUsage('--exclude=<BundleOne> --exclude=<BundleTwo>')
            ->addUsage('--fixtures-type=demo --exclude=<BundleOne> --exclude=<BundleTwo>')
            ->addUsage('--dry-run --fixtures-type=demo --exclude=<BundleOne> --exclude=<BundleTwo>')
            ->addLocalizationOptionsUsage()
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->inputOptionProvider = new InputOptionProvider($output, $input, $this->getHelperSet()->get('question'));

        $fixtures = null;
        try {
            $fixtures = $this->getFixtures($input, $output);
        } catch (\RuntimeException $ex) {
            $output->writeln('');
            $output->writeln(sprintf('<error>%s</error>', $ex->getMessage()));

            return $ex->getCode() == 0 ? 1 : $ex->getCode();
        }

        if (!empty($fixtures)) {
            if ($input->getOption('dry-run')) {
                $this->outputFixtures($input, $output, $fixtures);
            } else {
                $this->processFixtures($input, $output, $fixtures);
            }
        }

        return 0;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @throws \RuntimeException if loading of data fixtures should be terminated
     */
    protected function getFixtures(InputInterface $input, OutputInterface $output): array
    {
        $includeBundles = $input->getOption('bundles');
        $excludeBundles = $input->getOption('exclude');
        $fixtureRelativePath = $this->getFixtureRelativePath($input);

        /** @var BundleInterface[] $bundles */
        $bundles = $this->kernel->getBundles();

        foreach ($bundles as $bundle) {
            if (!empty($includeBundles) && !in_array($bundle->getName(), $includeBundles, true)) {
                continue;
            }
            if (!empty($excludeBundles) && in_array($bundle->getName(), $excludeBundles, true)) {
                continue;
            }
            $path = $bundle->getPath() . $fixtureRelativePath;
            if (is_dir($path)) {
                $this->dataFixturesLoader->loadFromDirectory($path);
            }
        }
        $this->loadAppFixtures($fixtureRelativePath);

        return $this->dataFixturesLoader->getFixtures();
    }

    protected function loadAppFixtures(string $relativeFixturePath): void
    {
        $appFixturesDirectory = $this->kernel->getProjectDir() . '/migrations';
        if (!is_dir($appFixturesDirectory)) {
            return;
        }
        $finder = (new Finder())->directories()->in($appFixturesDirectory);
        $relativeFixturePath = str_replace('/Migrations', '', $relativeFixturePath);
        foreach ($finder as $directory) {
            $fixtureItemDirectory = $directory . $relativeFixturePath;
            if (is_dir($fixtureItemDirectory)) {
                $this->dataFixturesLoader->loadFromDirectory($fixtureItemDirectory);
            }
        }
    }

    protected function outputFixtures(InputInterface $input, OutputInterface $output, array $fixtures): void
    {
        $output->writeln(
            sprintf(
                'List of "%s" data fixtures ...',
                $this->getTypeOfFixtures($input)
            )
        );
        foreach ($fixtures as $fixture) {
            $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', get_class($fixture)));
        }
    }

    protected function processFixtures(InputInterface $input, OutputInterface $output, array $fixtures): void
    {
        $output->writeln(
            sprintf(
                'Loading "%s" data fixtures (language: %s, formatting code: %s)...',
                $this->getTypeOfFixtures($input),
                $this->getLanguage($input),
                $this->getFormattingCode($input)
            )
        );

        $this->executeFixtures($input, $output, $fixtures, $this->getTypeOfFixtures($input));
    }

    protected function executeFixtures(
        InputInterface $input,
        OutputInterface $output,
        array $fixtures,
        string $fixturesType
    ): void {
        $cursor = new Cursor($output);
        $this->dataFixturesExecutor->setLogger(
            function ($message) use ($output, $cursor) {
                $output->write(\sprintf('  <comment>></comment> <info>%s</info>', $message));
                // Saves the cursor position to make it possible to return to the end of previous line in the progress
                // callback to append the progress info.
                $cursor->savePosition();
                $output->writeln('');
            }
        );

        $progressCallback = static function (int $memoryBytes, float $durationMilli) use ($output, $cursor) {
            // Returns cursor to the end of the previous line so the progress info will be appended after it.
            $cursor->restorePosition();
            $cursor->moveUp();

            $output->writeln(\sprintf(
                ' <comment>%.2F MiB - %d ms</comment>',
                $memoryBytes / 1024 / 1024,
                $durationMilli
            ));
        };

        $this->dataFixturesExecutor->setFormattingCode($this->getFormattingCode($input));
        $this->dataFixturesExecutor->setLanguage($this->getLanguage($input));

        $this->dataFixturesExecutor->execute($fixtures, $fixturesType, $progressCallback);
    }

    protected function getFormattingCode(InputInterface $input): string
    {
        $defaultFormattingCode = $this->getDefaultLocalization()?->getFormattingCode()
            ?? $this->kernel->getContainer()->getParameter(OroLocaleExtension::PARAMETER_FORMATTING_CODE);

        $interactive = $input->isInteractive();
        $input->setInteractive(false);

        $formattingCode = $this->getFormattingCodeFromOptions($defaultFormattingCode);

        $input->setInteractive($interactive);

        return $formattingCode;
    }

    protected function getLanguage(InputInterface $input): string
    {
        $defaultLanguage = $this->getDefaultLocalization()?->getLanguageCode()
            ?? $this->kernel->getContainer()->getParameter(OroLocaleExtension::PARAMETER_LANGUAGE);

        $interactive = $input->isInteractive();
        $input->setInteractive(false);

        $language = $this->getLanguageFromOptions($defaultLanguage);

        $input->setInteractive($interactive);

        return $language;
    }

    protected function getDefaultLocalization(): ?Localization
    {
        $defaultLocalization = null;

        $defaultLocalizationId = $this->configManager->get(
            Configuration::getConfigKeyByName(Configuration::DEFAULT_LOCALIZATION),
        );
        if ($defaultLocalizationId) {
            $defaultLocalization = $this->doctrine->getRepository(Localization::class)->find($defaultLocalizationId);
        }

        return $defaultLocalization;
    }

    protected function getTypeOfFixtures(InputInterface $input): string
    {
        return $input->getOption('fixtures-type');
    }

    protected function getFixtureRelativePath(InputInterface $input): string
    {
        $fixtureType = $this->getTypeOfFixtures($input);
        $fixtureRelativePath = $this->fixturePathLocator->getPath($fixtureType);

        return \str_replace('/', DIRECTORY_SEPARATOR, \sprintf('/%s', $fixtureRelativePath));
    }
}
