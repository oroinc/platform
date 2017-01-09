<?php

namespace Oro\Bundle\ReportBundle\Entity\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\ReportBundle\Entity\Repository\CalendarDateRepository;

class CalendarDateManager
{
    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * CalendarDateManager constructor.
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    public function handleCalendarDates($append = false)
    {
        $period = $this->getDatesFromInterval($append);
        $manager = $this->doctrineHelper->getEntityManager(CalendarDate::class);

        foreach ($period as $day) {
            $calendarDate = new CalendarDate();
            $calendarDate->setDate($day);
            $manager->persist($calendarDate);
        }

        $manager->flush();
    }

    public function getLastDate()
    {
        /** @var CalendarDateRepository $dateRepo */
        $dateRepo = $this->doctrineHelper->getEntityRepository(CalendarDate::class);

        return $dateRepo->getDate();
    }

    public function getDatesFromInterval($append = false)
    {
        $startDate = new \DateTime();
        $startDate->setDate($startDate->format('Y'), 1, 1);
        $startDate->setTime(0, 0, 0);

        if ($append) {
            $startDate = $this->getLastDate()->getDate();
        }

        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            new \DateTime('tomorrow + 1day'),
            \DatePeriod::EXCLUDE_START_DATE
        );

        return $period;
    }
}