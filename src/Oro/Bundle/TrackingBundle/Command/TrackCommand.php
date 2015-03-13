<?php

namespace Oro\Bundle\TrackingBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Component\Log\OutputLogger;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\TrackingBundle\Processor\TrackingProcessor;

class TrackCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const COMMAND_NAME = 'oro:cron:tracking:parse';

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/1 * * * *';
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Parse tracking logs');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var TrackingProcessor $processor */
        $processor = $this->getContainer()->get('oro_tracking.processor.tracking_processor');

        $processor->setLogger(new OutputLogger($output));
        $processor->process();
    }
}
