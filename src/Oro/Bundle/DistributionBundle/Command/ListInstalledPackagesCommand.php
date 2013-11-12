<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Oro\Bundle\DistributionBundle\Entity\Package;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListInstalledPackagesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:package:installed')
            ->setDescription('List of installed packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var Package[] $installedPackages */
        $installedPackages = $this->getContainer()->get('oro_distribution.package_manager')->getInstalled();

        foreach ($installedPackages as $package) {
            $output->writeln(sprintf("%s\t%s\t%s", $package->getName(), $package->getVersion(), $package->getDescription()));
        }
    }
}