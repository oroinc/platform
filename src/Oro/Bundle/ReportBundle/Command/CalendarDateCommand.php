<?php
declare(strict_types=1);

namespace Oro\Bundle\ReportBundle\Command;

use Oro\Bundle\CronBundle\Command\CronCommandScheduleDefinitionInterface;
use Oro\Bundle\ReportBundle\Entity\Manager\CalendarDateManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates calendar date records.
 */
class CalendarDateCommand extends Command implements CronCommandScheduleDefinitionInterface
{
    private const STATUS_SUCCESS = 0;

    /** @var string */
    protected static $defaultName = 'oro:cron:calendar:date';

    private CalendarDateManager $calendarDateManager;

    public function __construct(CalendarDateManager $calendarDateManager)
    {
        $this->calendarDateManager = $calendarDateManager;
        parent::__construct();
    }

    /**
     * {@inheritDoc}
     */
    public function getDefaultDefinition(): string
    {
        return '01 00 * * *';
    }

    /** @noinspection PhpMissingParentCallCommonInspection */
    protected function configure()
    {
        $this
            ->setDescription('Generates calendar date records.')
            ->setHelp(
                <<<'HELP'
The <info>%command.name%</info> command adds new date records to the database
to simplify grouping by date in some reports.

  <info>php %command.full_name%</info>

HELP
            )

        ;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     * @noinspection PhpMissingParentCallCommonInspection
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->calendarDateManager->handleCalendarDates(true);

        return self::STATUS_SUCCESS;
    }
}
