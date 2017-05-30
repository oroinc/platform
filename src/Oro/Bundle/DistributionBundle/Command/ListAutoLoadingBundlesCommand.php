<?php
namespace Oro\Bundle\DistributionBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Oro\Bundle\DistributionBundle\Console\Grid;

class ListAutoLoadingBundlesCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('oro:auto-loading-bundles:list')
            ->setDescription('List auto loading bundles by their priority');;
    }
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $bundles = $this->getApplication()->getKernel()->collectBundles();

        if ($bundles) {
            $output->writeln('<info>Auto loading bundles with priority:</info>');

            $grid = new Grid(3, ['=>', ':']);
            foreach ($bundles as $bundle) {
                $grid->addRow(
                    [$bundle['priority'], $bundle['name'], $bundle['kernel']]
                );
            }
            $output->writeln($grid->render());
            $output->writeln('');
        } else {
            $output->writeln('<comment>No auto loading bundles available</comment>');
        }
        return 0;
    }
}
