<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Tests\Functional\DataFixtures\LoadCalendarEventData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;

/**
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
        $this->calendarEvent = $this->getCalendarEventByTitle(LoadCalendarEventData::CALENDAR_EVENT_WITH_ATTENDEE);
    }

    public function testChangeInvitationStatus()
    {
        $availableStatuses = [
            CalendarEvent::STATUS_ACCEPTED,
            CalendarEvent::STATUS_TENTATIVE,
            CalendarEvent::STATUS_DECLINED
        ];

        foreach ($availableStatuses as $status) {
            $calendarEventId = $this->calendarEvent->getId();
            $this->client->request(
                'GET',
                $this->getUrl(
                    'oro_calendar_event_'.$status,
                    ['id' => $calendarEventId, 'status' => $status]
                )
            );
            $response = $this->client->getResponse();
            $data = json_decode($response->getContent(), true);
            $this->assertTrue($data['successful']);
            $calendarEvent = $this->getCalendarEventByTitle(LoadCalendarEventData::CALENDAR_EVENT_WITH_ATTENDEE);
            $this->assertNotNull($calendarEvent->getRelatedAttendee());
            $this->assertNotNull($calendarEvent->getRelatedAttendee()->getStatus());
            $this->assertEquals($status, $calendarEvent->getRelatedAttendee()->getStatus()->getId());
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
