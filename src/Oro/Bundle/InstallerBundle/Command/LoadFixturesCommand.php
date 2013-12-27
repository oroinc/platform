<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

class LoadFixturesCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:package:fixtures:load')
            ->setDescription('Load data fixtures from specified package(s) to your database.')
            ->addArgument(
                'package',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Package directories'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packageDirectories = $input->getArgument('package');
        foreach ($packageDirectories as $key => $packageDir) {
            $packageDirectories[$key] = realpath($packageDir) . DIRECTORY_SEPARATOR;
        }

        // prepare data fixture loader
        // we should load only fixtures from the specified packages
        $container   = $this->getContainer();
        $loader      = new ContainerAwareLoader($container);
        $hasFixtures = false;
        foreach ($container->get('kernel')->getBundles() as $bundle) {
            $bundleDir = $bundle->getPath();
            // check if a current bundle is related to any specified package
            foreach ($packageDirectories as $packageDir) {
                if (stripos($bundleDir, $packageDir) === 0) {
                    // check if a current bundle has fixtures
                    $fixtureDir = $bundleDir . '/DataFixtures/ORM';
                    if (is_dir($fixtureDir)) {
                        // register fixtures if both conditions success
                        $loader->loadFromDirectory($fixtureDir);
                        $hasFixtures = true;
                    }
                    break;
                }
            }
        }

        // load data fixtures
        if ($hasFixtures) {
            $output->writeln('Loading data ...');
            $executor = new ORMExecutor($container->get('doctrine.orm.entity_manager'));
            $executor->setLogger(
                function ($message) use ($output) {
                    $output->writeln(sprintf('  <comment>></comment> <info>%s</info>', $message));
                }
            );
            $executor->execute($loader->getFixtures(), true);
        }
    }
}
