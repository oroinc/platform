<?php

namespace Oro\Bundle\CalendarBundle\Tests\Functional\API;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

/**
 * @dbIsolation
 *
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class RestCalendarEventWithRecurrentEventTest extends AbstractCalendarEventTest
{
    /**
     * Creates recurring event.
     *
     * @return array
     */
    public function testPostRecurringEvent()
    {
        $recurringEvents = self::$recurringEventParameters;
        $recurringEvents['recurrence']['interval'] = '0'; //check that it will be validated
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), $recurringEvents);
        $this->getJsonResponseContent($this->client->getResponse(), 400);

        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        /** @var CalendarEvent $event */
        $event = $this->getContainer()->get('doctrine')
            ->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($event);
        $this->assertEquals(
            Recurrence::MAX_END_DATE,
            $event->getRecurrence()->getCalculatedEndTime()->format(DATE_RFC3339)
        );

        $activityTargetEntities = $event->getActivityTargetEntities();
        $this->assertCount(1, $activityTargetEntities);
        $this->assertEquals(
            $this->getReference('activity_target_one')->getId(),
            reset($activityTargetEntities)->getId()
        );

        return ['id' => $result['id'], 'recurrenceId' => $event->getRecurrence()->getId()];
    }

    /**
     * Reads recurring event.
     *
     * @depends testPostRecurringEvent
     *
     * @param array $data
     */
    public function testGetRecurringEvent($data)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $data['id']])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($data['id'], $result['id']);
        $this->assertEquals($data['recurrenceId'], $result['recurrence']['id']);
    }

    /**
     * Updates recurring event. The goal is to test transformation from recurring event to regular one.
     * Dependency for testPostRecurringEvent is not injected to work with own new recurring event.
     */
    public function testPutRecurringEvent()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $data['id'] = $event->getId();
        $data['recurrenceId'] = $event->getRecurrence()->getId();
        $recurringEventParameters = self::$recurringEventParameters;
        $recurringEventParameters['recurrence']['interval'] = '0';
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            $recurringEventParameters
        );
        $this->getJsonResponseContent($this->client->getResponse(), 400);

        $recurringEventParameters['recurrence'] = 'test_string'; //should be validated
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            $recurringEventParameters
        );
        $this->getJsonResponseContent($this->client->getResponse(), 400);

        $recurringEventParameters['recurrence'] = ''; // recurring event will become regular event.
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            $recurringEventParameters
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertFalse($result['notifiable']);

        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $data['id']]);
        $activityTargetEntities = $event->getActivityTargetEntities();
        $this->assertCount(1, $activityTargetEntities);
        $this->assertEquals(
            $this->getReference('activity_target_one')->getId(),
            reset($activityTargetEntities)->getId()
        );
        $this->assertNull($event->getRecurrence());
        $recurrence = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:Recurrence')
            ->findOneBy(['id' => $data['recurrenceId']]);
        $this->assertNull($recurrence);

        $em = $this->getContainer()->get('doctrine')->getManager();
        $em->remove($event);
        $em->flush();
    }

    /**
     * Deletes recurring event.
     * Dependency for testPostRecurringEvent is not injected to work with own new recurring event.
     */
    public function testDeleteRecurringEvent()
    {
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $data['id'] = $event->getId();
        $data['recurrenceId'] = $event->getRecurrence()->getId();
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $data['id']])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $data['id']]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
        $recurrence = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:Recurrence')
            ->findOneBy(['id' => $data['recurrenceId']]);
        $this->assertNull($recurrence);
    }

    /**
     * Creates recurring event exception.
     *
     * @depends testPostRecurringEvent
     *
     * @param array $data
     *
     * @return array
     */
    public function testPostRecurringEventException($data)
    {
        self::$recurringEventExceptionParameters['recurringEventId'] = $data['id'];
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_calendarevent'),
            self::$recurringEventExceptionParameters
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);

        $this->assertNotEmpty($result);
        $this->assertTrue(isset($result['id']));
        $exception = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $this->assertNotNull($exception);
        $this->assertEquals($data['id'], $exception->getRecurringEvent()->getId());
        $activityTargetEntities = $exception->getActivityTargetEntities();
        $this->assertCount(1, $activityTargetEntities);
        $this->assertEquals(
            $this->getReference('activity_target_one')->getId(),
            reset($activityTargetEntities)->getId()
        );

        return ['id' => $result['id'], 'recurringEventId' => $data['id']];
    }

    /**
     * Reads recurring event exception.
     *
     * @depends testPostRecurringEventException
     *
     * @param array $data
     */
    public function testGetRecurringEventException($data)
    {
        $this->client->request(
            'GET',
            $this->getUrl('oro_api_get_calendarevent', ['id' => $data['id']])
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);

        $this->assertNotEmpty($result);
        $this->assertEquals($data['id'], $result['id']);
        foreach (self::$recurringEventExceptionParameters as $attribute => $value) {
            $this->assertArrayHasKey($attribute, $result);
            $this->assertEquals(
                $value,
                $result[$attribute],
                sprintf('Failed assertion for $result["%s"] value: ', $attribute)
            );
        }
    }

    /**
     * Updates recurring event exception.
     *
     * @depends testPostRecurringEventException
     *
     * @param array $data
     */
    public function testPutRecurringEventException($data)
    {
        self::$recurringEventExceptionParameters['isCancelled'] = false;
        $this->client->request(
            'PUT',
            $this->getUrl('oro_api_put_calendarevent', ['id' => $data['id']]),
            self::$recurringEventExceptionParameters
        );

        $result = $this->getJsonResponseContent($this->client->getResponse(), 200);
        $this->assertFalse($result['notifiable']);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($data['id']);
        $this->assertEquals(self::$recurringEventExceptionParameters['isCancelled'], $event->isCancelled());
        $activityTargetEntities = $event->getActivityTargetEntities();
        $this->assertCount(1, $activityTargetEntities);
        $this->assertEquals(
            $this->getReference('activity_target_one')->getId(),
            reset($activityTargetEntities)->getId()
        );
    }

    /**
     * Deletes recurring event exception.
     *
     * @depends testPostRecurringEventException
     *
     * @param array $data
     */
    public function testDeleteRecurringEventException($data)
    {
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $data['id']])
        );

        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $data['id']]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
        $registry = $this->getContainer()->get('doctrine');
        $recurringEvent = $registry->getRepository('OroCalendarBundle:CalendarEvent')
            ->find(['id' => $data['recurringEventId']]);
        $registry->getManager()->remove($recurringEvent);
        $registry->getManager()->flush();
    }

    /**
     * Deletes recurring event with exception to ensure that all related entities are cascade removed.
     * Dependencies where not injected to handle with new objects.
     */
    public function testDeleteRecurringEventWithException()
    {
        // creates new recurring event
        $this->client->request('POST', $this->getUrl('oro_api_post_calendarevent'), self::$recurringEventParameters);
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->find($result['id']);
        $data['id'] = $event->getId();
        $data['recurrenceId'] = $event->getRecurrence()->getId();

        // creates new recurring event exception
        self::$recurringEventExceptionParameters['recurringEventId'] = $data['id'];
        $this->client->request(
            'POST',
            $this->getUrl('oro_api_post_calendarevent'),
            self::$recurringEventExceptionParameters
        );
        $result = $this->getJsonResponseContent($this->client->getResponse(), 201);
        $data['exceptionId'] = $result['id'];

        // deletes recurring events and all related entities
        $this->client->request(
            'DELETE',
            $this->getUrl('oro_api_delete_calendarevent', ['id' => $data['id']])
        );

        // ensures that all related entities are deleted
        $this->assertEmptyResponseStatusCodeEquals($this->client->getResponse(), 204);
        $event = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $data['id']]); // do not use 'load' method to avoid proxy object loading.
        $this->assertNull($event);
        $recurrence = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:Recurrence')
            ->findOneBy(['id' => $data['recurrenceId']]);
        $this->assertNull($recurrence);
        $exception = $this->getContainer()->get('doctrine')->getRepository('OroCalendarBundle:CalendarEvent')
            ->findOneBy(['id' => $data['exceptionId']]);
        $this->assertNull($exception);
    }
}
