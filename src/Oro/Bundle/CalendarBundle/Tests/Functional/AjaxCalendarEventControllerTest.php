<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
 * @outputBuffering enabled
 * @dbIsolation
 */
class AjaxCalendarEventControllerTest extends WebTestCase
{
    /** @var CalendarEvent $calendarEvent */
    protected $calendarEvent;

    protected function setUp()
    {
        $this->initClient([], $this->generateBasicAuthHeader());
        $this->loadFixtures(['Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventData']);
        $this->calendarEvent = $this->getCalendarEventByTitle(LoadCalendarEventData::CALENDAR_EVENT_TITLE);
    }

    public function testNotValidInvitationStatus()
    {
        $calendarEventId = $this->calendarEvent->getId();
        $this->client->request(
            'GET',
            $this->getUrl(
                'oro_calendar_event_change_status',
                ['id' => $calendarEventId, 'status' => 'wrong']
            )
        );
        $response = $this->client->getResponse();
        $this->assertEquals(500, $response->getStatusCode());
        $calendarEvent = $this->getCalendarEventByTitle(LoadCalendarEventData::CALENDAR_EVENT_TITLE);
        $this->assertSame(null, $calendarEvent->getInvitationStatus());
    }

    public function testChangeInvitationStatus()
    {
        $availableStatuses = [
            CalendarEvent::ACCEPTED,
            CalendarEvent::TENTATIVELY_ACCEPTED,
            CalendarEvent::DECLINED,
            CalendarEvent::NOT_RESPONDED,
        ];

        foreach ($availableStatuses as $status) {
            $calendarEventId = $this->calendarEvent->getId();
            $this->client->request(
                'GET',
                $this->getUrl(
                    'oro_calendar_event_change_status',
                    ['id' => $calendarEventId, 'status' => $status]
                )
            );
            $response = $this->client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($data['success']);
            $calendarEvent = $this->getCalendarEventByTitle(LoadCalendarEventData::CALENDAR_EVENT_TITLE);
            $this->assertEquals($status, $calendarEvent->getInvitationStatus());
        }
    }

    /**
     * @param string $title
     * @return CalendarEvent
     */
    protected function getCalendarEventByTitle($title)
    {
        return $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['title' => $title]);
    }
}
