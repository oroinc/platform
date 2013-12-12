<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Composer\Package\PackageInterface;
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

        foreach ($availablePackages as $package) {
            $output->writeln($package->getPrettyName());
        }
    }
}