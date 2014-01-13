<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Oro\Bundle\InstallerBundle\CommandExecutor;

class LoadPackageDemoDataCommand extends ContainerAwareCommand
{
    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('oro:package:demo:load')
            ->setDescription('Load demo data from specified package(s) to your database.')
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
        $suppliedPackagePaths = $input->getArgument('package');
        $packageDirectories = [];
        foreach ($suppliedPackagePaths as $package) {
            $path = realpath($package);
            if (!$path) {
                $output->writeln(sprintf('<error>Path "%s" is invalid</error>', $package));
                continue;
            }

            $packageDirectories[] = $path;
        }

        if (!$packageDirectories) {
            $output->writeln('');
            $output->writeln('<error>No valid paths specified</error>');
            return 1;
        }


        // a function which allows filter fixtures by the given packages
        $filterByPackage = function ($path) use ($packageDirectories) {
            foreach ($packageDirectories as $packageDir) {
                if (stripos($path, $packageDir) === 0) {
                    return true;
                }
            }

            return false;
        };

        // prepare data fixture loader
        // we should load only fixtures from the specified packages
        $container = $this->getContainer();
        $loader = new ContainerAwareLoader($container);
        $hasFixtures = false;
        foreach ($container->get('kernel')->getBundles() as $bundle) {
            $fixtureDir = $bundle->getPath() . DIRECTORY_SEPARATOR . 'DataFixtures' . DIRECTORY_SEPARATOR . 'Demo';
            if (is_dir($fixtureDir) && $filterByPackage($fixtureDir)) {
                $output->writeln($fixtureDir);
                $loader->loadFromDirectory($fixtureDir);
                $hasFixtures = true;
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

