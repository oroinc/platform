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
        'targetCalendar' => ['id' => 1],
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
        $calendarConnections = $this->soapClient->getCalendarConnections();
        $calendarConnections = $this->valueToArray($calendarConnections);
        $this->assertCount(2, $calendarConnections['item']);
    }

    /**
     * @param integer $id
     * @depends testCreate
     */
    public function testGet($id)
    {
        $calendarConnection = $this->soapClient->getCalendarConnection($id);
        $calendarConnection = $this->valueToArray($calendarConnection);
        $this->assertEquals($this->calendarProperty['calendarAlias'], $calendarConnection['calendarAlias']);
        $this->assertEquals($this->calendarProperty['calendar'], $calendarConnection['calendar']);
    }

    /**
     * @param integer $id
     * @depends testCreate
     */
    public function testUpdate($id)
    {
        $calendarProperty =  array_merge($this->calendarProperty, ['color' => '000000']);

        $result = $this->soapClient->updateCalendarConnection($id, $calendarProperty);
        $this->assertTrue($result);

        $calendarConnection = $this->soapClient->getCalendarConnection($id);
        $calendarConnection = $this->valueToArray($calendarConnection);

        $this->assertEquals($calendarConnection['color'], $calendarProperty['color']);
    }

    /**
     * @param integer $id
     * @depends testCreate
     */
    public function testDelete($id)
    {
        $result = $this->soapClient->deleteCalendarConnection($id);
        $this->assertTrue($result);

        $this->setExpectedException('\SoapFault', 'Record with ID "' . $id . '" can not be found');
        $this->soapClient->getCalendarConnection($id);
    }
}
