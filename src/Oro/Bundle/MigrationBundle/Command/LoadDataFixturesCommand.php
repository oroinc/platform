<?php

namespace Oro\Bundle\MigrationBundle\Command;

use Oro\Bundle\MigrationBundle\Locator\FixturePathLocatorInterface;
use Oro\Bundle\MigrationBundle\Migration\DataFixturesExecutorInterface;
use Oro\Bundle\MigrationBundle\Migration\Loader\DataFixturesLoader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * This command load fixtures
 *
 * @package Oro\Bundle\MigrationBundle\Command
 */
class LoadDataFixturesCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:migration:data:load';

    const MAIN_FIXTURES_TYPE = DataFixturesExecutorInterface::MAIN_FIXTURES;
    const DEMO_FIXTURES_TYPE = DataFixturesExecutorInterface::DEMO_FIXTURES;

    /** @deprecated since 2.6 please use oro_migration.locator.fixture_path_locator */
    const MAIN_FIXTURES_PATH = 'Migrations/Data/ORM';

    /** @deprecated since 2.6 please use oro_migration.locator.fixture_path_locator */
    const DEMO_FIXTURES_PATH = 'Migrations/Data/Demo/ORM';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(static::COMMAND_NAME)
            ->setDescription('Load data fixtures.')
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
        /** @var DataFixturesLoader $loader */
        $loader = $this->getContainer()->get('oro_migration.data_fixtures.loader');
        $includeBundles = $input->getOption('bundles');
        $excludeBundles = $input->getOption('exclude');
        $fixtureRelativePath = $this->getFixtureRelativePath($input);

        /** @var BundleInterface[] $bundles */
        $bundles = $this->getApplication()->getKernel()->getBundles();
        foreach ($bundles as $bundle) {
            if (!empty($includeBundles) && !in_array($bundle->getName(), $includeBundles, true)) {
                continue;
            }
            if (!empty($excludeBundles) && in_array($bundle->getName(), $excludeBundles, true)) {
                continue;
            }
            $path = $bundle->getPath() . $fixtureRelativePath;
            if (is_dir($path)) {
                $loader->loadFromDirectory($path);
            }
        }

        return $loader->getFixtures();
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
        /** @var DataFixturesExecutorInterface $loader */
        $executor = $this->getContainer()->get('oro_migration.data_fixtures.executor');
        $executor->setLogger(
            function ($message) use ($output) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        );
        $executor->execute($fixtures, $fixturesType);
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
        $fixtureRelativePath = $this->getFixturePathLocator()->getPath($fixtureType);

        return str_replace('/', DIRECTORY_SEPARATOR, sprintf('/%s', $fixtureRelativePath));
    }

    /**
     * @return FixturePathLocatorInterface
     */
    protected function getFixturePathLocator(): FixturePathLocatorInterface
    {
        return $this->getContainer()->get('oro_migration.locator.fixture_path_locator');
    }
}
