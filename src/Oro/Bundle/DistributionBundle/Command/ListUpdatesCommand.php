<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Oro\Bundle\DistributionBundle\Console\Grid;
use Oro\Bundle\DistributionBundle\Manager\PackageManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListUpdatesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:package:updates')
            ->setDescription('Lists available updates for installed packages');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var PackageManager $manager */
        $manager = $this->getContainer()->get('oro_distribution.package_manager');
        $updates = $manager->getAvailableUpdates();
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
