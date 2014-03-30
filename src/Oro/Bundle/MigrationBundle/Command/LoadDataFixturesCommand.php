<?php

namespace Oro\Bundle\MigrationBundle\Command;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

use Oro\Bundle\MigrationBundle\Migration\Loader\DataFixturesLoader;

class LoadDataFixturesCommand extends ContainerAwareCommand
{
    const MAIN_FIXTURES_TYPE = 'main';
    const DEMO_FIXTURES_TYPE = 'demo';

    const MAIN_FIXTURES_PATH = 'Migrations/Data/ORM';
    const DEMO_FIXTURES_PATH = 'Migrations/Data/Demo/ORM';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:migration:data:load')
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
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $indexListener = $this->getContainer()->get('oro_search.index_listener');
        $indexListener->disablePostFlush();

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
        $loader              = $this->getContainer()->get('oro_migration.data_fixtures.loader');
        $bundles             = $input->getOption('bundles');
        $excludeBundles      = $input->getOption('exclude');
        $fixtureRelativePath = $this->getFixtureRelativePath($input);

        /** @var BundleInterface $bundle */
        foreach ($this->getApplication()->getKernel()->getBundles() as $bundle) {
            if (!empty($bundles) && !in_array($bundle->getName(), $bundles)) {
                continue;
            }
            if (!empty($excludeBundles) && in_array($bundle->getName(), $excludeBundles)) {
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

        $executor = new ORMExecutor($this->getContainer()->get('doctrine.orm.entity_manager'));
        $executor->setLogger(
            function ($message) use ($output) {
                $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
            }
        );
        $executor->execute($fixtures, true);
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
     * @return string
     */
    protected function getFixtureRelativePath(InputInterface $input)
    {
        $fixtureRelativePath = $this->getTypeOfFixtures($input) == self::DEMO_FIXTURES_TYPE
            ? self::DEMO_FIXTURES_PATH
            : self::MAIN_FIXTURES_PATH;

        return str_replace('/', DIRECTORY_SEPARATOR, '/' . $fixtureRelativePath);
    }
}
