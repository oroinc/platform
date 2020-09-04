<?php

namespace Oro\Bundle\InstallerBundle\Command;

use Oro\Bundle\MigrationBundle\Command\LoadDataFixturesCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpKernel\Bundle\BundleInterface;

/**
 * Loads demo data from specified package(s) to your database.
 */
class LoadPackageDemoDataCommand extends LoadDataFixturesCommand
{
    /** @var string */
    protected static $defaultName = 'oro:package:demo:load';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setDescription('Load demo data from specified package(s) to your database.')
            ->addArgument(
                'package',
                InputArgument::IS_ARRAY | InputArgument::REQUIRED,
                'Package directories'
            )
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Outputs list of fixtures without apply them');
    }

    /**
     * @inheritdoc
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function getFixtures(InputInterface $input, OutputInterface $output)
    {
        $suppliedPackagePaths = $input->getArgument('package');
        $packageDirectories = [];
        foreach ($suppliedPackagePaths as $package) {
            $path = realpath($package);
            if (!$path) {
                $output->writeln(sprintf('<error>Path "%s" is invalid</error>', $package));
                continue;
            }

            $packageDirectories[] = $path . DIRECTORY_SEPARATOR;
        }

        if (!$packageDirectories) {
            throw new \RuntimeException('No valid paths specified', 1);
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
        $fixtureRelativePath = $this->getFixtureRelativePath($input);

        /** @var BundleInterface $bundle */
        foreach ($this->kernel->getBundles() as $bundle) {
            $bundleDir = $bundle->getPath();
            if (is_dir($bundleDir) && $filterByPackage($bundleDir)) {
                $path = $bundleDir . $fixtureRelativePath;
                if (is_dir($path)) {
                    $this->dataFixturesLoader->loadFromDirectory($path);
                }
            }
        }

        return $this->dataFixturesLoader->getFixtures();
    }

    /**
     * @inheritdoc
     */
    protected function getTypeOfFixtures(InputInterface $input)
    {
        return LoadDataFixturesCommand::DEMO_FIXTURES_TYPE;
    }
}
