<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\ReportBundle\Entity\Repository\CalendarDateRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @dbIsolation
 */
class CalendarDateRepositoryTest extends WebTestCase
{
    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
    }

    public function testGetDate()
    {
        $lastDate = $this->getLastCalendarDate()->getDate();
        /** @var CalendarDate $calendarDate */
        $calendarDate = $this->getRepository()->getDate();
        $this->assertEquals($lastDate->format('Y-m-d'), $calendarDate->getDate()->format('Y-m-d'));
    }

    public function testGetDateWithExistingArgument()
    {
        $fistDate = $this->getFirstCalendarDate()->getDate();
        /** @var CalendarDate $calendarDate */
        $calendarDate = $this->getRepository()->getDate($fistDate);
        $this->assertEquals($fistDate->format('Y-m-d'), $calendarDate->getDate()->format('Y-m-d'));
    }

    public function testGetDateWithNotExistingArgument()
    {
        $lastDate = $this->getLastCalendarDate()->getDate()->modify('+1 day');
        $this->assertNull($this->getRepository()->getDate($lastDate));
        $firstDate = $this->getFirstCalendarDate()->getDate()->modify('-1 day');
        $this->assertNull($this->getRepository()->getDate($firstDate));
    }

    public function testGetDateWithEmptyTable()
    {
        $this->emptyTable();
        $this->assertNull($this->getRepository()->getDate());
    }

    /**
     * @return CalendarDateRepository
     */
    protected function getRepository()
    {
        return $this->getContainer()->get('doctrine')->getRepository(CalendarDate::class);
    }

    /**
     * @return CalendarDate
     */
    protected function getLastCalendarDate()
    {
        return $this->getRepository()->findOneBy([], ['date' => 'DESC']);
    }

    /**
     * @return CalendarDate
     */
    protected function getFirstCalendarDate()
    {
        return $this->getRepository()->findOneBy([], ['date' => 'ASC']);
    }

    protected function emptyTable()
    {
        $records = $this->getRepository()->findAll();
        $manager = $this->getContainer()->get('doctrine')->getManager();
        foreach ($records as $record) {
            $manager->remove($record);
        }

        $manager->flush();
    }
}
