<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Composer\Package\PackageInterface;
use Oro\Bundle\DistributionBundle\Console\Grid;
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
        /** @var PackageInterface[] $installedPackages */
        $installedPackages = $this->getContainer()->get('oro_distribution.package_manager')->getInstalled();

        $grid = new Grid(2, [':']);
        foreach ($installedPackages as $package) {
            $grid->addRow([$package->getName(), $package->getPrettyVersion()]);
        }
        $output->writeln($grid->render());
    }
}