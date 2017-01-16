<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional\Command;

use Oro\Bundle\ReportBundle\Command\CalendarDateCommand;
use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CalendarDateCommandTest extends WebTestCase
{
    const DATE_FORMAT = 'Y-m-d';
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures([]);
    }

    public function testGenerateDates()
    {
        $repository = $this->getContainer()->get('doctrine')->getRepository(CalendarDate::class);
        $results = $repository->findAll();
        $this->assertCalendarDates($results);

        $manager = $this->getContainer()->get('doctrine')->getManagerForClass(CalendarDate::class);
        foreach ($results as $result) {
            $manager->remove($result);
        }
        $manager->flush();

        $this->runCommand(CalendarDateCommand::COMMAND_NAME);
        $this->assertCalendarDates($repository->findAll());
    }

    /**
     * @param array $calendarDates
     */
    protected function assertCalendarDates(array $calendarDates)
    {
        $generatedDates = [];
        foreach ($calendarDates as $calendarDate) {
            /** @var CalendarDate $calendarDate */
            $generatedDates[$calendarDate->getDate()->format(self::DATE_FORMAT)] = $calendarDate->getDate();
        }

        $requiredDates = $this->getGeneratedDates();
        foreach ($requiredDates as $date) {
            /** @var \DateTime $date */
            $this->assertArrayHasKey($date->format(self::DATE_FORMAT), $generatedDates);
        }
    }

    /**
     * @return array
     */
    private function getGeneratedDates()
    {
        $dates = [];
        $period = new \DatePeriod(new \DateTime('first day of this year'), new \DateInterval('P1D'), new \DateTime());
        foreach ($period as $date) {
            $dates[] = $date;
        }

        return $dates;
    }
}
