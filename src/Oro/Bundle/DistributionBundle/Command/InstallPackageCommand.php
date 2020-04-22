<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Oro\Bundle\DistributionBundle\Entity\PackageRequirement;
use Oro\Bundle\DistributionBundle\Exception\VerboseException;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Oro\Component\PhpUtils\PhpIniUtil;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Forces installing of dependencies. No confirmation will be ask
 */
class InstallPackageCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:package:install';

    /** @var PackageManager */
    private $packageManager;

    /**
     * @param PackageManager $packageManager
     */
    public function __construct(PackageManager $packageManager)
    {
        $this->packageManager = $packageManager;
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
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

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
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

        if ($this->packageManager->isPackageInstalled($packageName)) {
            return $output->writeln(
                sprintf('<error>%s has been already installed. Try to update it</error>', $packageName)
            );
        }

        $loadDemoData = $dialog->askConfirmation(
            $output,
            'Do you want to load demo data? (yes/no, default - no) ',
            false
        );

        $requirements = $this->packageManager->getRequirements($packageName, $packageVersion);

        if (!$forceDependenciesInstalling && $requirements) {
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
            $this->packageManager->install($packageName, $packageVersion, $loadDemoData);
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
        if ($memoryLimit !== -1.0 && $memoryLimit < $minimalSuggestedMemory) {
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
