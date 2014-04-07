<?php

namespace Oro\Bundle\DashboardBundle\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\DashboardBundle\Configuration\ConfigurationLoader;

class LoadDashboardCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:dashboard:load';

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Load dashboard definitions from configuration files to the database'
            )
            ->addOption(
                'directories',
                'd',
                InputOption::VALUE_OPTIONAL | InputOption::VALUE_IS_ARRAY,
                'Directories used to find configuration files'
            );
    }

    /**
     * @inheritdoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $usedDirectories = $input->getOption('directories');
        $usedDirectories = $usedDirectories ? : null;

        $container = $this->getContainer();

        /** @var ConfigurationLoader $configurationLoader */
        $configurationLoader = $container->get('oro_dashboard.configuration.loader');
        $configurations      = $configurationLoader->getDashboardConfiguration($usedDirectories);
        if ($configurations) {
            $configurationManager = $container->get('oro_dashboard.configuration.manager');
            $em                   = $container->get('doctrine.orm.entity_manager');

            $dashboards = [];
            $output->writeln('Load dashboard configuration');
            foreach ($configurations as $dashboardName => $dashboardConfiguration) {
                $output->writeln(
                    sprintf('  <comment>></comment> <info>%s</info>', $dashboardName)
                );
                $dashboards[] = $configurationManager->saveConfiguration(
                    $dashboardName,
                    $dashboardConfiguration
                );
            }

            $em->flush($dashboards);
        } else {
            $output->writeln('No dashboard configuration found.');
        }
    }
}
