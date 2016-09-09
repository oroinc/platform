<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\Controller\API\Soap;

use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 * @group soap
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
        'backgroundColor' => '#FFFFFF',
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
        $calendarConnections = array_filter(
            $this->valueToArray($this->soapClient->getCalendarConnections())['item'],
            function ($item) {
                $property = array_filter($this->calendarProperty, function ($item) {
                    return !is_array($item);
                });

                $item = array_filter($item, function ($item) {
                    return !is_array($item);
                });

                $diff = array_diff($property, $item);

                return empty($diff);
            }
        );

        $this->assertNotEmpty($calendarConnections);
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
        $calendarProperty =  array_merge($this->calendarProperty, ['backgroundColor' => '#000000']);

        $result = $this->soapClient->updateCalendarConnection($id, $calendarProperty);
        $this->assertTrue($result);

        $calendarConnection = $this->soapClient->getCalendarConnection($id);
        $calendarConnection = $this->valueToArray($calendarConnection);

        $this->assertEquals($calendarConnection['backgroundColor'], $calendarProperty['backgroundColor']);
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
