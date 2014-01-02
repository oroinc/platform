<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Oro\Bundle\InstallerBundle\CommandExecutor;

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
        // prepare directories of specified packages
        $packageDirectories = $input->getArgument('package');
        foreach ($packageDirectories as $key => $packageDir) {
            $packageDirectories[$key] = realpath($packageDir) . DIRECTORY_SEPARATOR;
        }

        // a function which allows filter fixtures by the given packages
        $filterByPackage = function ($path) use (&$packageDirectories) {
            foreach ($packageDirectories as $packageDir) {
                if (stripos($path, $packageDir) === 0) {
                    return true;
                }
            }

            return false;
        };

        // prepare data fixture loader
        // we should load only fixtures from the specified packages
        $container   = $this->getContainer();
        $loader      = new ContainerAwareLoader($container);
        $hasFixtures = false;
        foreach ($container->get('kernel')->getBundles() as $bundle) {
            $fixtureDir = $bundle->getPath() . DIRECTORY_SEPARATOR . 'DataFixtures' . DIRECTORY_SEPARATOR . 'ORM';
            if (is_dir($fixtureDir) && $filterByPackage($fixtureDir)) {
                $loader->loadFromDirectory($fixtureDir);
                $hasFixtures = true;
            }
            break;
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

        // load workflow definitions
        $this->loadWorkflowDefinitions($input, $output);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function loadWorkflowDefinitions(InputInterface $input, OutputInterface $output)
    {
        $commandExecutor = new CommandExecutor(
            $input->hasOption('env') ? $input->getOption('env') : null,
            $output,
            $this->getApplication()
        );

        $commandExecutor->runCommand(
            'oro:workflow:definitions:load',
            array_merge(
                $input->getArgument('package'),
                array('--process-isolation' => true)
            )
        );
    }
}
