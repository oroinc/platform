<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;


class InstallPackageCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:package:install')
            ->addArgument('package', InputArgument::REQUIRED, 'Package name to be installed')
            ->addArgument('version', InputArgument::OPTIONAL, 'Package version to be installed')
            ->setDescription('Installs package from repository');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packageName = $input->getArgument('package');
        $packageVersion = $input->getArgument('version');
        /** @var PackageManager $manager */
        $manager = $this->getContainer()->get('oro_distribution.package_manager');

        if ($manager->isPackageInstalled($packageName)) {
            throw new \RuntimeException(sprintf('Package %s has been already installed. Try to update', $packageName));
        }
        $package = $manager->getPreferredPackage($packageName, $packageVersion);
        $requirements = $manager->getRequirements($package);
        if ($requirements) {
            $output->writeln(sprintf("Package requires: \n%s", implode("\n", $requirements)));
        }

        $output->writeln(sprintf('%s has been installed!', $packageName));
    }
}