<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Oro\Bundle\InstallerBundle\Migrations\DataFixturesLoader;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

class LoadDataFixturesCommand extends ContainerAwareCommand
{
    const FIXTURES_PATH           = 'Migrations/DataFixtures/ORM';
    const DEMO_DATA_FIXTURES_PATH = 'Migrations/DataFixtures/Demo/ORM';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:installer:fixtures:load')
            ->setDescription('Load versioned data fixtures.')
            ->addOption(
                'fixtures-type',
                null,
                InputOption::VALUE_OPTIONAL,
                'Select fixtures type to be loaded (main or demo). By default - main',
                'main'
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
        $container = $this->getContainer();
        /** @var DataFixturesLoader $loader */
        $loader = $container->get('oro_installer.fixtures.loader');

        $indexListener = $container->get('oro_search.index_listener');
        $indexListener->disablePostFlush();

        $bundles             = $input->getOption('bundles');
        $excludeBundles      = $input->getOption('exclude');
        $fixtureRelativePath = $input->getOption('fixtures-type') == 'demo'
            ? self::FIXTURES_PATH
            : self::DEMO_DATA_FIXTURES_PATH;

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

        $fixtures = $loader->getFixtures();

        if ($input->getOption('dry-run')) {
            $this->outputFixtures($input, $output, $fixtures);
        } else {
            $this->processFixtures($input, $output, $fixtures);
        }
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
                $input->getOption('fixtures-type')
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
                $input->getOption('fixtures-type')
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
}
