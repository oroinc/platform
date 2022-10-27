<?php

namespace Oro\Bundle\ReportBundle\Entity\Manager;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\LocaleBundle\Model\LocaleSettings;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\ReportBundle\Entity\Repository\CalendarDateRepository;

/**
 * Inserts calendar dates since the beginning of the year till current date or appends dates from the last one.
 */
class CalendarDateManager
{
    /** @var DoctrineHelper */
    protected $doctrineHelper;

    /** @var LocaleSettings */
    protected $localeSettings;

    public function __construct(DoctrineHelper $doctrineHelper, LocaleSettings $localeSettings)
    {
        $this->doctrineHelper = $doctrineHelper;
        $this->localeSettings = $localeSettings;
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
        $timeZone = new \DateTimeZone($this->localeSettings->getTimeZone());
        $startDate = new \DateTime('now midnight', $timeZone);
        $startDate->setDate($startDate->format('Y'), 1, 1);

        if ($append) {
            $lastDate = $this->getLastDate();
            $startDate = $lastDate ? $lastDate->add(new \DateInterval('P1D')) : $startDate;
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
            return clone $calendarDate->getDate();
        }

        return null;
    }
}
