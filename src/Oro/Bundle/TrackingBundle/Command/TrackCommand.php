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
    const STATUS_SUCCESS = 0;
    const COMMAND_NAME = 'oro:cron:tracking:parse';

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '*/15 * * * *';
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
        $logger = new OutputLogger($output);
        if ($this->getContainer()->get('oro_cron.job_manager')->getRunningJobsCount(self::COMMAND_NAME) > 1) {
            $logger->warning('Parsing job already running. Terminating current job.');

            return self::STATUS_SUCCESS;
        }

        /** @var TrackingProcessor $processor */
        $processor = $this->getContainer()->get('oro_tracking.processor.tracking_processor');

        $processor->setLogger($logger);
        $processor->process();

        return self::STATUS_SUCCESS;
    }
}
