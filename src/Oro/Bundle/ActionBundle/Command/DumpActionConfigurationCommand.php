<?php

namespace Oro\Bundle\ActionBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\ActionBundle\Configuration\ConfigurationProvider;

class DumpActionConfigurationCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:action:configuration:dump')
            ->setDescription('Dump action configuration')
            ->addArgument('action', InputArgument::OPTIONAL, 'Names of the action that should be dumped');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Load actions ...');

        $errors = new ArrayCollection();
        $configuration = $this->getConfigurationProvider()->getConfiguration(true, $errors);

        if ($configuration) {
            $action = $input->getArgument('action');

            if ($action && isset($configuration[$action])) {
                $output->writeln($action);
                print_r($configuration[$action]);
            } else {
                foreach ($configuration as $key => $value) {
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
    protected function getConfigurationProvider()
    {
        return $this->getContainer()->get('oro_action.configuration.provider.operations');
    }
}
