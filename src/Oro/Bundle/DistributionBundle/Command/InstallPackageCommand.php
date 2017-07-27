<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Oro\Bundle\DistributionBundle\Entity\PackageRequirement;
use Oro\Bundle\DistributionBundle\Exception\VerboseException;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Component\PhpUtils\PhpIniUtil;
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
        /** @var DialogHelper $dialog */
        $dialog = $this->getHelperSet()->get('dialog');

        if (!$this->checkSuggestions($output)) {
            $continue = $dialog->askConfirmation(
                $output,
                'Some suggestions were not met. Do you want to continue? (yes/no, default - no) ',
                false
            );

            if (!$continue) {
                return 0;
            }
        }

        $packageName = $input->getArgument('package');
        $packageVersion = $input->getArgument('version');
        $forceDependenciesInstalling = $input->getOption('force');
        $verbose = $input->getOption('verbose');

        /** @var PackageManager $manager */
        $manager = $this->getContainer()->get('oro_distribution.package_manager');

        if ($manager->isPackageInstalled($packageName)) {
            return $output->writeln(
                sprintf('<error>%s has been already installed. Try to update it</error>', $packageName)
            );
        }

        $loadDemoData = $dialog->askConfirmation(
            $output,
            'Do you want to load demo data? (yes/no, default - no) ',
            false
        );

        if (!$forceDependenciesInstalling && $requirements = $manager->getRequirements($packageName, $packageVersion)) {
            $requirementsString = array_reduce(
                $requirements,
                function ($result, PackageRequirement $requirement) {
                    $result .= PHP_EOL . ' - ' . $requirement->getName();
                    if ($requirement->isInstalled()) {
                        $result .= ' [installed]';
                    }

                    return $result;
                },
                ''
            );
            $output->writeln(sprintf("%s requires:%s", $packageName, $requirementsString));

            if (!$dialog->askConfirmation($output, 'All missing packages will be installed. Continue? (yes/no) ')) {
                return $output->writeln('<comment>Process aborted</comment>');
            }
        }

        try {
            $manager->install($packageName, $packageVersion, $loadDemoData);
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            if ($verbose && $e instanceof VerboseException) {
                $output->writeln(sprintf('<comment>%s</comment>', $e->getVerboseMessage()));
            }

            return 1;
        }

        $output->writeln(sprintf('%s has been installed!', $packageName));

        return 0;
    }

    /**
     * @param OutputInterface $output
     *
     * @return bool True if suggestions are met, false otherwise
     */
    protected function checkSuggestions(OutputInterface $output)
    {
        $warnings = [];

        $minimalSuggestedMemory = 2 * pow(1024, 3);
        $memoryLimit = PhpIniUtil::parseBytes(ini_get('memory_limit'));
        if ($memoryLimit !== -1 && $memoryLimit < $minimalSuggestedMemory) {
            $warnings[] = '<comment>It\'s recommended at least 2Gb to be available for PHP CLI</comment>';
        }

        if (extension_loaded('xdebug')) {
            $warnings[] = '<comment>You are about to run composer with xdebug enabled. '
                . 'This has a major impact on runtime performance. See https://getcomposer.org/xdebug</comment>';
        }

        array_walk($warnings, [$output, 'writeln']);

        return !$warnings;
    }
}
