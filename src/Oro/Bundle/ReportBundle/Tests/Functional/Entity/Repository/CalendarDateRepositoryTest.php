<?php

namespace Oro\Bundle\ReportBundle\Tests\Functional\Entity\Repository;

use Oro\Bundle\ReportBundle\Entity\CalendarDate;
use Oro\Bundle\ReportBundle\Entity\Repository\CalendarDateRepository;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

class CalendarDateRepositoryTest extends WebTestCase
{
    /**
     * {@inheritdoc}
     */
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
        $firstDate = $this->getFirstCalendarDate()->getDate();
        $lastDate = $this->getLastCalendarDate()->getDate();

        /** @var CalendarDate $calendarDate */
        $calendarDate = $this->getRepository()->getDate($firstDate);
        $this->assertEquals($firstDate->format('Y-m-d'), $calendarDate->getDate()->format('Y-m-d'));

        /** @var CalendarDate $calendarDate */
        $calendarDate = $this->getRepository()->getDate($lastDate);
        $this->assertEquals($lastDate->format('Y-m-d'), $calendarDate->getDate()->format('Y-m-d'));
    }

    public function testGetDateWithNotExistingArgument()
    {
        $lastDate = clone $this->getLastCalendarDate()->getDate();
        $lastDate->modify('+1 day');
        $this->assertNull($this->getRepository()->getDate($lastDate));

        $firstDate = clone $this->getFirstCalendarDate()->getDate();
        $firstDate->modify('-1 day');
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
