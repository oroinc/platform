<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Oro\Component\PropertyAccess\PropertyAccessor;

/**
 * @dbIsolation
 */
class RestCalendarEventTest extends WebTestCase
{
    const DEFAULT_USER_CALENDAR_ID = 1;

    /** @var array */
    protected $calendarEventDocument;

    protected function setUp()
    {
        $this->initClient(array(), $this->generateWsseAuthHeader());
        $this->calendarEventDocument = [
            'title' => 'Test Event',
            'description' => 'Test Description',
            'start' => date(DATE_RFC3339),
            'end' => date(DATE_RFC3339),
            'allDay' => true,
            'backgroundColor' => '#FF0000',
            'calendar' => self::DEFAULT_USER_CALENDAR_ID,
            'recurrence' => [
                'recurrenceType' => Recurrence::TYPE_DAILY,
                'interval' => 1,
                'instance' => null,
                'dayOfWeek' => [],
                'dayOfMonth' => null,
                'monthOfYear' => null,
                'startTime' => date(DATE_RFC3339),
                'endTime' => null,
                'occurrences' => null,
                'exceptions' => [
                    [
                        'originalDate' => date(DATE_RFC3339),
                        'title' => 'Test Exception Title',
                        'description' => 'Test Description of Exception',
                        'start' => date(DATE_RFC3339),
                        'end' => date(DATE_RFC3339),
                        'allDay' => false,
                    ]
                ],
            ],
        ];
    }

    public function testGets()
    {
        $request = array(
            "calendar" => self::DEFAULT_USER_CALENDAR_ID,
            "start" => date(DATE_RFC3339, strtotime('-1 day')),
            "end" => date(DATE_RFC3339, strtotime('+1 day')),
            'subordinate' => false
        );

        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertEmpty($result);
    }

    /**
     * Create new event
     *
     * @return int
     */
    public function testPost()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $this->calendarEventDocument);

        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));

        return $result['id'];
    }

    /**
     * @depends testPost
     *
     * @param int $id
     *
     * @return int
     */
    public function testPut($id)
    {
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', array("id" => $id)),
            $this->calendarEventDocument
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);

        return $id;
    }

    /**
     * @depends testPut
     *
     * @param int $id
     */
    public function testGet($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $id])
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);
        $calendarEventDocument = $this->calendarEventDocument;
        //event listener must change endTime date
        $calendarEventDocument['recurrence']['endTime'] = '9000-01-01T00:00:01+00:00';
        $this->clearAttributes($result);
        $this->clearAttributes($calendarEventDocument);
        $this->assertEquals($calendarEventDocument, $result);
    }

    /**
     * @depends testPut
     *
     * @param int $id
     */
    public function testGetByCalendar($id)
    {
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_api_get_calendarevent_by_calendar',
                ['id' => self::DEFAULT_USER_CALENDAR_ID, 'eventId' => $id]
            )
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result['id']);
    }

    /**
     * @depends testPut
     *
     * @param int $id
     */
    public function testCget($id)
    {
        $request = array(
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'start'       => date(DATE_RFC3339, strtotime('-1 day')),
            'end'         => date(DATE_RFC3339, strtotime('+1 day')),
            'subordinate' => true
        );
        $this->client->request('GET', $this->getUrl('oro_api_get_calendarevents', $request));

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($id, $result[0]['id']);
    }

    /**
     * @depends testPut
     */
    public function testCgetFiltering()
    {
        $request = array(
            'calendar'    => self::DEFAULT_USER_CALENDAR_ID,
            'page'        => 1,
            'limit'       => 10,
            'subordinate' => false
        );
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            . '&createdAt>' . urlencode('2014-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertCount(1, $result);
        $result = $result[0];
        $calendarEventDocument = $this->calendarEventDocument;
        //event listener must change endTime date
        $calendarEventDocument['recurrence']['endTime'] = '9000-01-01T00:00:01+00:00';
        $this->clearAttributes($result);
        $this->clearAttributes($calendarEventDocument);
        $this->assertEquals($calendarEventDocument, $result);

        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevents', $request)
            . '&createdAt>' . urlencode('2050-03-04T20:00:00+0000')
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertEmpty($result);
    }

    /**
     * @param array $document
     */
    protected function clearAttributes(&$document)
    {
        $propertyAccessor = new PropertyAccessor();
        $keysToRemove = [
            // keys that present in response, but not in put/post
            'id',
            'createdAt',
            'updatedAt',
            'invitationStatus',
            'invitedUsers',
            'editable',
            'removable',
            'notifiable',
            'parentEventId',
            'use_hangout',
            // dates come with local time, but pass by UTC
            'start',
            'end',
            '[recurrence][id]',
            '[recurrence][startTime]',
            '[recurrence][exceptions][0][id]',
            '[recurrence][exceptions][0][originalDate]',
            '[recurrence][exceptions][0][start]',
            '[recurrence][exceptions][0][end]',
        ];
        foreach ($keysToRemove as $path) {
            $propertyAccessor->remove($document, $path);
        }
    }
}
