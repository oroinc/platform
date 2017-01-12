<?php

namespace Oro\Bundle\ReportBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\ReportBundle\Entity\Manager\CalendarDateManager;
use Oro\Component\Log\OutputLogger;

class CalendarDateCommand extends ContainerAwareCommand implements CronCommandInterface
{
    const STATUS_SUCCESS = 0;
    const COMMAND_NAME   = 'oro:cron:calendar:date';

    /**
     * {@inheritdoc}
     */
    public function getDefaultDefinition()
    {
        return '01 00 * * *';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive()
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName(self::COMMAND_NAME)
            ->setDescription('Generate calendar dates');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $logger = new OutputLogger($output);
        if ($this->getContainer()->get('oro_cron.schedule_manager')->getRunningJobsCount(self::COMMAND_NAME) > 1) {
            $logger->warning('Parsing job already running. Terminating current job.');

            return self::STATUS_SUCCESS;
        }

        /** @var CalendarDateManager $calendarDateManager */
        $calendarDateManager = $this->getContainer()->get('oro_report.calendar_date_manager');

        $calendarDateManager->handleCalendarDates(true);

        return self::STATUS_SUCCESS;
    }
}
