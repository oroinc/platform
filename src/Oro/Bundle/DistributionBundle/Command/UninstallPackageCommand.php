<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UninstallPackageCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:package:uninstall')
            ->addArgument('package', InputArgument::REQUIRED, 'Package name to be uninstalled')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Forces uninstalling of dependents packages. No confirmation will be ask'
            )
            ->setDescription('Uninstalls package');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packageName = $input->getArgument('package');
        $forceDependentsUninstalling = $input->getOption('force');

        /** @var PackageManager $manager */
        $manager = $this->getContainer()->get('oro_distribution.package_manager');

        if (!$manager->isPackageInstalled($packageName)) {
            return $output->writeln(sprintf('Package %s is not yet installed', $packageName));
        }

        $dependents = $manager->getDependents($packageName);
        if (!$forceDependentsUninstalling && $dependents) {
            $output->writeln(sprintf("%s is required by: \n%s", $packageName, implode("\n", $dependents)));
            /** @var DialogHelper $dialog */
            $dialog = $this->getHelperSet()->get('dialog');
            if (!$dialog->askConfirmation($output, 'Do you want to uninstall all them? (yes/no) ')) {
                return $output->writeln('<comment>Process aborted</comment>');
            }
        }

        $manager->uninstall(array_merge($dependents, [$packageName]));
        $output->writeln(sprintf('%s has been uninstalled!', $packageName));
        return 0;
    }
}
