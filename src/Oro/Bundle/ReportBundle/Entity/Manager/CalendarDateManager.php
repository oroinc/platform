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
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param bool $append
     */
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

    /**
     * @param bool $append
     * @return \DatePeriod
     */
    protected function getDatesFromInterval($append = false)
    {
        if ($append) {
            $startDate = $this->getLastDate();
        }

        if (empty($startDate)) {
            $startDate = new \DateTime();
            $startDate->setDate($startDate->format('Y'), 1, 1);
            $startDate->setTime(0, 0, 0);
        }

        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            new \DateTime('tomorrow + 1day'),
            \DatePeriod::EXCLUDE_START_DATE
        );

        return $period;
    }

    /**
     * @return \DateTime|null
     */
    protected function getLastDate()
    {
        /** @var CalendarDateRepository $dateRepository */
        $dateRepository = $this->doctrineHelper->getEntityRepository(CalendarDate::class);

        $calendarDate = $dateRepository->getDate();
        if ($calendarDate) {
            return $calendarDate->getDate();
        }

        return null;
    }
}
