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
        $timeZone = new \DateTimeZone('UTC');
        $startDate = new \DateTime('now midnight', $timeZone);
        $startDate->setDate($startDate->format('Y'), 1, 1);

        if ($append) {
            $startDate = $this->getLastDate() ?: $startDate;
        }

        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), new \DateTime('tomorrow midnight', $timeZone));

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
