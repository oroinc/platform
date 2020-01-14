<?php

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
 * The CLI command to load data fixtures.
 */
class LoadDataFixturesCommand extends Command
{
    const MAIN_FIXTURES_TYPE = DataFixturesExecutorInterface::MAIN_FIXTURES;
    const DEMO_FIXTURES_TYPE = DataFixturesExecutorInterface::DEMO_FIXTURES;

    /** @var string */
    protected static $defaultName = 'oro:migration:data:load';

    /** @var KernelInterface */
    protected $kernel;

    /** @var DataFixturesLoader */
    protected $dataFixturesLoader;

    /** @var DataFixturesExecutorInterface */
    protected $dataFixturesExecutor;

    /** @var FixturePathLocatorInterface */
    protected $fixturePathLocator;

    /**
     * @param KernelInterface $kernel
     * @param DataFixturesLoader $dataFixturesLoader
     * @param DataFixturesExecutorInterface $dataFixturesExecutor
     * @param FixturePathLocatorInterface $fixturePathLocator
     */
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

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Load data fixtures.')
            ->addOption(
                'fixtures-type',
                null,
                InputOption::VALUE_OPTIONAL,
                sprintf(
                    'Select fixtures type to be loaded (%s or %s). By default - %s',
                    self::MAIN_FIXTURES_TYPE,
                    self::DEMO_FIXTURES_TYPE,
                    self::MAIN_FIXTURES_TYPE
                ),
                self::MAIN_FIXTURES_TYPE
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Outputs list of fixtures without apply them')
            ->addOption(
                'bundles',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'A list of bundle names to load data from'
            )
            ->addOption(
                'exclude',
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'A list of bundle names which fixtures should be skipped'
            );
    }

    /**
     * {@inheritdoc}
     */
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return array
     * @throws \RuntimeException if loading of data fixtures should be terminated
     */
    protected function getFixtures(InputInterface $input, OutputInterface $output)
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

    /**
     * Output list of fixtures
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $fixtures
     */
    protected function outputFixtures(InputInterface $input, OutputInterface $output, $fixtures)
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

    /**
     * Process fixtures
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @param array           $fixtures
     */
    protected function processFixtures(InputInterface $input, OutputInterface $output, $fixtures)
    {
        $output->writeln(
            sprintf(
                'Loading "%s" data fixtures ...',
                $this->getTypeOfFixtures($input)
            )
        );

        $this->executeFixtures($output, $fixtures, $this->getTypeOfFixtures($input));
    }

    /**
     * @param OutputInterface $output
     * @param array           $fixtures
     * @param string          $fixturesType
     */
    protected function executeFixtures(OutputInterface $output, $fixtures, $fixturesType)
    {
        $this->dataFixturesExecutor->setLogger(
            function ($message) use ($output) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        );
        $this->dataFixturesExecutor->execute($fixtures, $fixturesType);
    }

    /**
     * @param InputInterface $input
     * @return string
     */
    protected function getTypeOfFixtures(InputInterface $input)
    {
        return $input->getOption('fixtures-type');
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected function getFixtureRelativePath(InputInterface $input)
    {
        $fixtureType         = (string)$this->getTypeOfFixtures($input);
        $fixtureRelativePath = $this->fixturePathLocator->getPath($fixtureType);

        return str_replace('/', DIRECTORY_SEPARATOR, sprintf('/%s', $fixtureRelativePath));
    }
}
