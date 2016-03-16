<?php

namespace Oro\Bundle\ActionBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProvider;
use Oro\Bundle\ActionBundle\Model\ActionGroupRegistry;
use Oro\Bundle\ActionBundle\Model\OperationRegistry;

class DumpActionConfigurationCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:action:configuration:dump')
            ->setDescription('Dump action configuration')
            ->addArgument('action', InputArgument::OPTIONAL, 'Names of the action that should be dumped')
            ->addOption('action-group', null, InputOption::VALUE_NONE, 'Dump action_group')
            ->addOption('assemble', null, InputOption::VALUE_NONE, 'Assemble configuration');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Load actions ...');

        $errors = new ArrayCollection();

        if ($input->getOption('action-group')) {
            $provider = $this->getActionGroupsProvider();
        } else {
            $provider = $this->getOperationsProvider();
        }

        $configuration = $provider->getConfiguration(true, $errors);

        if ($input->getOption('assemble')) {
            if ($input->getOption('action-group')) {
                $registry = $this->getActionGroupRegistry();
            } else {
                $registry = $this->getOperationRegistry();
            }

            foreach ($configuration as $name => &$value) {
                $value = $registry->findByName($name)->getDefinition();
            }
        }

        if ($configuration) {
            $action = $input->getArgument('action');

            if ($action && isset($configuration[$action])) {
                $output->writeln($action);
                print_r($configuration[$action]);
            } else {
                foreach (array_keys($configuration) as $key) {
                    $output->writeln($key);
                }
            }
        } else {
            $output->writeln('No actions found.');
        }
    }

    /**
     * @return ConfigurationProvider
     */
    protected function getOperationsProvider()
    {
        return $this->getContainer()->get('oro_action.configuration.provider.operations');
    }

    /**
     * @return ConfigurationProvider
     */
    protected function getActionGroupsProvider()
    {
        return $this->getContainer()->get('oro_action.configuration.provider.action_groups');
    }

    /**
     * @return ActionGroupRegistry
     */
    public function getActionGroupRegistry()
    {
        return $this->getContainer()->get('oro_action.action_group_registry');
    }

    /**
     * @return OperationRegistry
     */
    public function getOperationRegistry()
    {
        return $this->getContainer()->get('oro_action.operation_registry');
    }
}
