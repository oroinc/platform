<?php

namespace Oro\Bundle\DashboardBundle\Command;

use Doctrine\ORM\EntityManager;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\DashboardBundle\Exception\InvalidArgumentException;
use Oro\Bundle\UserBundle\Entity\User;

class LoadDashboardCommand extends ContainerAwareCommand
{
    const COMMAND_NAME = 'oro:dashboard:load';

    /**
     * @var EntityManager
     */
    protected $em;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(
                'Load dashboard definitions from configuration files to the database'
            )
            ->addOption(
                'username',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Dashboards owner username'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = $input->getOption('username');
        $username = $username ? : null;

        $container = $this->getContainer();

        $this->em              = $container->get('doctrine.orm.entity_manager');
        $configurationProvider = $container->get('oro_dashboard.config_provider');
        $configurationLoader   = $container->get('oro_dashboard.model.dashboard_loader');

        $dashboards = [];
        foreach ($configurationProvider->getDashboardConfigs() as $dashboardName => $dashboardConfig) {
            /* @todo: move to config provider */
            foreach ($dashboardConfig['widgets'] as $widgetName => $widgetOptions) {
                $dashboardConfig['widgets'][$widgetName] = array_merge(
                    $configurationProvider->getWidgetConfig($widgetName),
                    $widgetOptions
                );
            }

            $dashboards[] = $configurationLoader->saveDashboardConfiguration(
                $dashboardName,
                $dashboardConfig,
                $this->getUser($username)
            );

            $output->writeln(
                sprintf('  <comment>></comment> <info>%s</info>', $dashboardName)
            );
        }

        $this->em->flush($dashboards);


        $output->writeln('Load dashboard configuration');
    }

    /**
     * @param string $username
     * @return User
     * @throws InvalidArgumentException
     */
    protected function getUser($username)
    {
        if ($username) {
            $repository = $this->em->getRepository('OroUserBundle:User');
            $user       = $repository->findOneBy(['username' => $username]);
        } else {
            $repository = $this->em->getRepository('OroUserBundle:Role');
            $role       = $repository->findOneBy(['role' => User::ROLE_ADMINISTRATOR]);
            $user       = $repository->getFirstMatchedUser($role);

            if (!$user) {
                throw new InvalidArgumentException(
                    'At least one user needed to configure dashboard ownership'
                );
            }
        }

        return $user;
    }
}
