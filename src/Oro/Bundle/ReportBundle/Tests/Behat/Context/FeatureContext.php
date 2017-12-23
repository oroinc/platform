<?php

namespace Oro\Bundle\ReportBundle\Tests\Behat\Context;

use Behat\Symfony2Extension\Context\KernelAwareContext;
use Behat\Symfony2Extension\Context\KernelDictionary;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\TestFrameworkBundle\Behat\Context\OroFeatureContext;

class FeatureContext extends OroFeatureContext implements KernelAwareContext
{
    use KernelDictionary;

    /**
     * @Given /^I have a complete calendar date table from "(?P<year>[\d]+)"$/
     * @param int $year
     */
    public function iHaveCompleteCalendarDateTable($year)
    {
        $startDate = new \DateTime('now midnight', new \DateTimeZone('UTC'));
        $startDate->setDate($year, 1, 1);

        $this->fillDatesFrom($startDate);
    }

    /**
     * @param \DateTime $startDate
     */
    protected function fillDatesFrom(\DateTime $startDate)
    {
        $registry = $this->getContainer()->get('doctrine');

        $timeZone = new \DateTimeZone('UTC');
        $manager = $registry->getManagerForClass(CalendarDate::class);
        $currentDates = $manager->getRepository(CalendarDate::class)->findAll();

        /** @var CalendarDate $date */
        $currentIndexedDates = [];
        foreach ($currentDates as $date) {
            $currentIndexedDates[$date->getDate()->format('Y-m-d H:i:s')] = $date;
        }

        $period = new \DatePeriod(
            $startDate,
            new \DateInterval('P1D'),
            new \DateTime('tomorrow midnight', $timeZone)
        );

        foreach ($period as $day) {
            if (!array_key_exists($day->format('Y-m-d H:i:s'), $currentIndexedDates)) {
                $calendarDate = new CalendarDate();
                $calendarDate->setDate($day);
                $manager->persist($calendarDate);
            }
        }
        $manager->flush();
    }
}
