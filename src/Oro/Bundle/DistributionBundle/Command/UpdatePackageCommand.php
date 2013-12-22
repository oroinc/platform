<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Oro\Bundle\DistributionBundle\Exception\VerboseException;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdatePackageCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:package:update')
            ->addArgument('package', InputArgument::REQUIRED, 'Package name to be updated')
            ->setDescription('Updates package if new version is available');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packageName = $input->getArgument('package');
        $verbose = $input->getOption('verbose');

        /** @var PackageManager $manager */
        $manager = $this->getContainer()->get('oro_distribution.package_manager');

        if (!$manager->isPackageInstalled($packageName)) {
            $output->writeln(sprintf('<error>Package %s is not yet installed</error>', $packageName));
            $output->writeln(sprintf('Run <comment>oro:package:install %s</comment> to install', $packageName));
            return 1;
        }

        if (!$manager->isUpdateAvailable($packageName)) {
            $output->writeln(sprintf('No updates available for package <comment>%s</comment>', $packageName));
            return 1;
        }

        try {
            $manager->update($packageName);
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            if ($verbose && $e instanceof VerboseException) {
                $output->writeln(sprintf('<comment>%s</comment>', $e->getVerboseMessage()));
            }
        }

        $output->writeln(sprintf('<comment>%s updated!</comment>', $packageName));
        return 0;
    }
}
