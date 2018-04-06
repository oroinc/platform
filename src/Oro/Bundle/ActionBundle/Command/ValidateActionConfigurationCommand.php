<?php

namespace Oro\Bundle\ActionBundle\Command;

use Doctrine\Common\Collections\ArrayCollection;
use Oro\Bundle\ActionBundle\Configuration\ConfigurationProviderInterface;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ValidateActionConfigurationCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('oro:action:configuration:validate')
            ->setDescription('Validate action configuration');
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
            $output->writeln(sprintf('Found %d action(s) with %d error(s)', count($configuration), count($errors)));

            foreach ($errors as $error) {
                $output->writeln($error);
            }
        } else {
            $output->writeln('No actions found.');
        }
    }

    /**
     * @return ConfigurationProviderInterface
     */
    protected function getConfigurationProvider()
    {
        return $this->getContainer()->get('oro_action.configuration.provider.operations');
    }
}
