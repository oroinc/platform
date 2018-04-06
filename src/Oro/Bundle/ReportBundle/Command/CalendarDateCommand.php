<?php

namespace Oro\Bundle\ReportBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\ReportBundle\Entity\Manager\CalendarDateManager;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
        /** @var CalendarDateManager $calendarDateManager */
        $calendarDateManager = $this->getContainer()->get('oro_report.calendar_date_manager');
        $calendarDateManager->handleCalendarDates(true);

        return self::STATUS_SUCCESS;
    }
}
