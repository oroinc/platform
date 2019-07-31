<?php

namespace Oro\Bundle\DistributionBundle\Command;

use Oro\Bundle\DistributionBundle\Exception\VerboseException;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Updates package if new version is available
 */
class UpdatePackageCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:package:update';

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
            ->addArgument('package', InputArgument::REQUIRED, 'Package name to be updated')
            ->setDescription('Updates package if new version is available');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $packageName = $input->getArgument('package');
        $verbose     = $input->getOption('verbose');

        if (!$this->packageManager->isPackageInstalled($packageName)) {
            $output->writeln(sprintf('<error>Package %s is not yet installed</error>', $packageName));
            $output->writeln(sprintf('Run <comment>oro:package:install %s</comment> to install', $packageName));

            return 1;
        }

        if (!$this->packageManager->isUpdateAvailable($packageName)) {
            $output->writeln(sprintf('No updates available for package <comment>%s</comment>', $packageName));

            return 1;
        }

        try {
            $this->packageManager->update($packageName);

            $output->writeln(sprintf('<comment>%s updated!</comment>', $packageName));
        } catch (\Exception $e) {
            $output->writeln(sprintf('<error>%s</error>', $e->getMessage()));
            if ($verbose && $e instanceof VerboseException) {
                $output->writeln(sprintf('<comment>%s</comment>', $e->getVerboseMessage()));
            }

            return 1;
        }

        return 0;
    }
}
