<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Composer\Package\PackageInterface;
use Oro\Bundle\DistributionBundle\Console\Grid;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListAvailablePackagesCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:package:available')
            ->setDescription('List of available packages');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var PackageInterface[] $availablePackages */
        $availablePackages = $this->getContainer()->get('oro_distribution.package_manager')->getAvailable();

        $grid = new Grid(2, [':']);
        foreach ($availablePackages as $package) {
            $grid->addRow([$package->getName(), $package->getPrettyVersion()]);
        }

        $output->writeln($grid->render());
    }
}
