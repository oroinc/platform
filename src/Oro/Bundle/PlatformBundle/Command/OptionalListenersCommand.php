<?php

namespace Oro\Bundle\PlatformBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class OptionalListenersCommand extends ContainerAwareCommand
{
    const NAME = 'oro:platform:optional-listeners';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Get list of Doctrine listeners which can be disabled during process console command');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $listeners = $this->getContainer()->get('oro_platform.optional_listeners.manager')->getListeners();
        $output->writeln('List of optional listeners:');
        foreach ($listeners as $listener) {
            $output->writeln(sprintf('  <comment>> %s</comment>', $listener));
        }
    }
}
