<?php

namespace Oro\Bundle\ReportBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandInterface;
use Oro\Bundle\ReportBundle\Entity\Manager\CalendarDateManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate calendar dates
 */
class CalendarDateCommand extends Command implements CronCommandInterface
{
    private const STATUS_SUCCESS = 0;

    /** @var string */
    protected static $defaultName = 'oro:cron:calendar:date';

    /** @var CalendarDateManager */
    private $calendarDateManager;

    /**
     * @param CalendarDateManager $calendarDateManager
     */
    public function __construct(CalendarDateManager $calendarDateManager)
    {
        $this->calendarDateManager = $calendarDateManager;
        parent::__construct();
    }

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
        $this->setDescription('Generate calendar dates');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->calendarDateManager->handleCalendarDates(true);

        return self::STATUS_SUCCESS;
    }
}
