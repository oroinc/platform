<?php

namespace Oro\Bundle\DistributionBundle\Command;

use Oro\Bundle\DistributionBundle\Console\Grid;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Lists available updates for installed packages
 */
class ListUpdatesCommand extends Command
{
    /** @var string */
    protected static $defaultName = 'oro:package:updates';

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
        $this->setDescription('Lists available updates for installed packages');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $updates = $this->packageManager->getAvailableUpdates();
        if ($updates) {
            $output->writeln('<info>Following updates are available:</info>');

            $grid = new Grid(3, [':', '=>']);
            foreach ($updates as $update) {
                $grid->addRow(
                    [$update->getPackageName(), $update->getCurrentVersionString(), $update->getUpToDateVersionString()]
                );
            }
            $output->writeln($grid->render());
            $output->writeln('');
            $output->writeln('run <comment>oro:package:update <info>package</info></comment> to update');
        } else {
            $output->writeln('<comment>No updates available</comment>');
        }
        return 0;
    }
}
