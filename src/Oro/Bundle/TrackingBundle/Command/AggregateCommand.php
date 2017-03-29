<?php

namespace Oro\Bundle\TrackingBundle\Command;

use Oro\Bundle\TrackingBundle\Tools\UniqueTrackingVisitDumper;
use Oro\Component\Log\OutputLogger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AggregateCommand extends ContainerAwareCommand
{
    const STATUS_SUCCESS = 0;
    const STATUS_FAILED = 255;
    const COMMAND_NAME = 'oro:tracking:aggregate';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Aggregate tracking visits');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new OutputLogger($output);
        $logger->info('<info>Starting tracking visits aggregation</info>');
        if ($this->getContainer()->get('oro_cron.job_manager')->getRunningJobsCount(self::COMMAND_NAME) > 1) {
            $logger->warning('Aggregation job already running. Terminating current job.');

            return self::STATUS_SUCCESS;
        }
        if (!$this->getContainer()->get('oro_config.global')->get('oro_tracking.precalculated_statistic_enabled')) {
            $logger->warning('Aggregation disabled. Terminating current job.');

            return self::STATUS_SUCCESS;
        }

        /** @var UniqueTrackingVisitDumper $dumper */
        $dumper = $this->getContainer()->get('oro_tracking.tools.unique_tracking_visit_dumper');

        if ($dumper->refreshAggregatedData()) {
            $logger->info('<info>Tracking visits aggregation complete</info>');

            return self::STATUS_SUCCESS;
        } else {
            $logger->error('<error>Tracking visits aggregation failed</error>');

            return self::STATUS_FAILED;
        }
    }
}
