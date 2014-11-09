<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Controller\API\Soap;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class CalendarConnectionControllerTest extends WebTestCase
{
    /**
     * @var array
     */
    protected $calendarProperty = [
        'calendarAlias' => 'test',
        'calendar' => 1,
        'targetCalendar' => 1,
        'visible' => true,
        'position' => 0,
        'color' => 'FFFFFF',
        'backgroundColor' => 'FFFFFF',
    ];


    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->initSoapClient();
    }

    /**
     * @return integer
     */
    public function testCreate()
    {
        $result = $this->soapClient->createCalendarConnection($this->calendarProperty);
        $this->assertTrue((bool) $result, $this->soapClient->__getLastResponse());

        return $result;
    }

    /**
     * @depends testCreate
     */
    public function testCget()
    {
        $calendarConnections = $this->soapClient->getCalendarConnections(1);
        $calendarConnections = $this->valueToArray($calendarConnections);
        $this->assertCount(2, $calendarConnections['item']);
    }

    /**
     * @param integer $id
     * @depends testCreate
     */
    public function testUpdate($id)
    {
        $calendarProperty =  array_merge($this->calendarProperty, ['position' => 100, 'calendar' => 2]);

        $result = $this->soapClient->updateCalendarConnection($id, $calendarProperty);
        $this->assertTrue($result);

        $calendarConnections = $this->soapClient->getCalendarConnections(1);
        $calendarConnections = $this->valueToArray($calendarConnections);

        $this->assertEquals($calendarConnections['item'][1]['position'], $calendarConnections['item'][1]['position']);
    }

    /**
     * @param integer $id
     * @depends testCreate
     */
    public function testDelete($id)
    {
        $result = $this->soapClient->deleteCalendarConnection($id);
        $this->assertTrue($result);

        $calendarConnections = $this->soapClient->getCalendarConnections(1);
        $calendarConnections = $this->valueToArray($calendarConnections);
        $this->assertEquals($calendarConnections['item']['targetCalendar'], 1);
    }
}
