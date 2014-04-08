<?php

namespace Oro\Bundle\DashboardBundle\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\DashboardBundle\Provider\ConfigProvider;

class LoadDashboardCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:dashboard:load';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Load dashboard definitions from configuration files to the database'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getContainer();

        /** @var ConfigProvider $configurationProvider */
        $configurationManager = $container->get('oro_dashboard.configuration.manager');
        $em                   = $container->get('doctrine.orm.entity_manager');

        $output->writeln('Load dashboard configuration');

        $dashboards = $configurationManager->saveDashboardConfigurations();
        if ($dashboards) {
            foreach ($dashboards as $dashboard) {
                $output->writeln(
                    sprintf('  <comment>></comment> <info>%s</info>', $dashboard->getName())
                );
            }

            $em->flush($dashboards);
        }
    }
}
