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
        'calendarAlias' => 'user',
        'calendar' => 1,
        'targetCalendar' => 1,
        'visible' => 1,
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
        $this->assertCount(1, $calendarConnections);
    }
}
