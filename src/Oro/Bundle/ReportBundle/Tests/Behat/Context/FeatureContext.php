<?php

namespace Oro\Bundle\ReportBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderAwareInterface;
use Oro\Bundle\TestFrameworkBundle\Behat\Fixtures\FixtureLoaderDictionary;

class FeatureContext extends OroFeatureContext implements KernelAwareContext, FixtureLoaderAwareInterface
{
    use KernelDictionary;
    use FixtureLoaderDictionary;

    /**
     * @Given /^I have a complete calendar date table from "(?P<fromYear>[\d]+)" to "(?P<toYear>[\d]+)"$/
     * @param int $fromYear
     * @param int $toYear
     */
    public function iHaveCompleteCalendarDateTable($fromYear, $toYear)
    {
        $this->clearCalendarDateTable();

        $startDate = new \DateTime(sprintf('first day of January %d midnight', $fromYear), new \DateTimeZone('UTC'));
        $endDate = new \DateTime(sprintf('first day of January %d noon', $toYear), new \DateTimeZone('UTC'));

        $this->fillDatesFrom($startDate, $endDate);
    }

    protected function clearCalendarDateTable()
    {
        $em = $this->getContainer()
            ->get('doctrine')
            ->getManagerForClass(CalendarDate::class);

        $repository = $em->getRepository(CalendarDate::class);

        foreach ($repository->findAll() as $calendarDate) {
            $em->remove($calendarDate);
        }

        $em->flush();
    }

    /**
     * @param \DateTime $startDate
     * @param \DateTime $endDate
     */
    protected function fillDatesFrom(\DateTime $startDate, \DateTime $endDate)
    {
        $registry = $this->getContainer()->get('doctrine');

        $manager = $registry->getManagerForClass(CalendarDate::class);
        $currentDates = $manager->getRepository(CalendarDate::class)->findAll();

        /** @var CalendarDate $date */
        $currentIndexedDates = [];
        foreach ($currentDates as $date) {
            $currentIndexedDates[$date->getDate()->format(\DateTime::ISO8601)] = $date;
        }

        $period = new \DatePeriod($startDate, new \DateInterval('P1D'), $endDate);

        $dates = [];

        /** @var \DateTime $day */
        foreach ($period as $day) {
            if (!array_key_exists($day->format(\DateTime::ISO8601), $currentIndexedDates)) {
                $calendarDate = new CalendarDate();
                $calendarDate->setDate($day);

                $dates[] = $calendarDate;
            }
        }

        $this->fixtureLoader->persist($dates);
    }
}
