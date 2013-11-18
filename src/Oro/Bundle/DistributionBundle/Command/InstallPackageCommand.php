<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;


class InstallPackageCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:package:install')
            ->addArgument('package', InputArgument::REQUIRED, 'Package name to be installed')
            ->addArgument('version', InputArgument::OPTIONAL, 'Package version to be installed')
            ->addOption(
                'force',
                'f',
                InputOption::VALUE_NONE,
                'Forces installing of dependencies. No confirmation will be ask'
            )
            ->setDescription('Installs package from repository');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packageName = $input->getArgument('package');
        $packageVersion = $input->getArgument('version');
        $forceDependenciesInstalling = $input->getOption('force');

        /** @var PackageManager $manager */
        $manager = $this->getContainer()->get('oro_distribution.package_manager');

        if ($manager->isPackageInstalled($packageName)) {
            throw new \RuntimeException(sprintf('Package %s has been already installed. Try to update', $packageName));
        }
        $package = $manager->getPreferredPackage($packageName, $packageVersion);
        $requirements = $manager->getRequirements($package);
        if ($requirements) {
            $output->writeln(sprintf("Package requires: \n%s", implode("\n", $requirements)));

            if (!$forceDependenciesInstalling) {
                /** @var DialogHelper $dialog */
                $dialog = $this->getHelperSet()->get('dialog');
                if (!$dialog->askConfirmation($output, 'Do you want to install all them? (yes/no) ')) {
                    $output->writeln('<comment>Process aborted</comment>');
                    return 1;
                }
            }
        }
        $manager->addToComposerJsonFile($package);
        $output->writeln('composer.json has been dumped');

        if ($manager->install($package)) {
            $output->writeln(sprintf('%s has been installed!', $packageName));
        } else {
            $output->writeln(sprintf('<error>%s can\'t be installed!</error>', $packageName));
            if ($input->getOption('verbose')) {
                $output->writeln($this->getContainer()->get('oro_distribution.composer.io')->getOutput());
            }
        }

        return 0;
    }
}