<?php
declare(strict_types=1);

namespace Oro\Bundle\MigrationBundle\Command;

use Oro\Bundle\MigrationBundle\Locator\FixturePathLocatorInterface;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutorInterface;
use Oro\Bundle\MigrationBundle\Migration\Loader\DataFixturesLoader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;
use Symfony\Component\HttpKernel\KernelInterface;

/**
 * Loads data fixtures.
 */
class LoadDataFixturesCommand extends Command
{
    public const MAIN_FIXTURES_TYPE = DataFixturesExecutorInterface::MAIN_FIXTURES;
    public const DEMO_FIXTURES_TYPE = DataFixturesExecutorInterface::DEMO_FIXTURES;

    /** @var string */
    protected static $defaultName = 'oro:migration:data:load';

    protected KernelInterface $kernel;
    protected DataFixturesLoader $dataFixturesLoader;
    protected DataFixturesExecutorInterface $dataFixturesExecutor;
    protected FixturePathLocatorInterface $fixturePathLocator;

    public function __construct(
        KernelInterface $kernel,
        DataFixturesLoader $dataFixturesLoader,
        DataFixturesExecutorInterface $dataFixturesExecutor,
        FixturePathLocatorInterface $fixturePathLocator
    ) {
        parent::__construct();

        $this->kernel = $kernel;
        $this->dataFixturesLoader = $dataFixturesLoader;
        $this->dataFixturesExecutor = $dataFixturesExecutor;
        $this->fixturePathLocator = $fixturePathLocator;
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
        ;
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
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

        return $this->dataFixturesLoader->getFixtures();
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
                'Loading "%s" data fixtures ...',
                $this->getTypeOfFixtures($input)
            )
        );

        $this->executeFixtures($output, $fixtures, $this->getTypeOfFixtures($input));
    }

    protected function executeFixtures(OutputInterface $output, array $fixtures, string $fixturesType): void
    {
        $this->dataFixturesExecutor->setLogger(
            function ($message) use ($output) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        );
        $this->dataFixturesExecutor->execute($fixtures, $fixturesType);
    }

    protected function getTypeOfFixtures(InputInterface $input): string
    {
        return $input->getOption('fixtures-type');
    }

    protected function getFixtureRelativePath(InputInterface $input): string
    {
        $fixtureType         = (string)$this->getTypeOfFixtures($input);
        $fixtureRelativePath = $this->fixturePathLocator->getPath($fixtureType);

        return str_replace('/', DIRECTORY_SEPARATOR, sprintf('/%s', $fixtureRelativePath));
    }
}
