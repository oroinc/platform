<?php

namespace Oro\Bundle\TrackingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Component\Log\OutputLogger;

class TrackCommand extends ContainerAwareCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('oro:tracking:parse')
            ->setDescription('Import tracking logs');
    }

    /**
     * {@internaldoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $processor = $this->getContainer()->get('oro_tracking.processor.tracking_processor');
        $processor->setLogger(new OutputLogger($output));
        $processor->process();
    }
}
