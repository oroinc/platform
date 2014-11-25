<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\DataTransformer;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\DataTransformer\EventsToUsersTransformer;
use Oro\Bundle\UserBundle\Entity\User;

class EventsToUsersTransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $registry;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject
     */
    protected $securityFacade;

    /**
     * @var EventsToUsersTransformer
     */
    protected $transformer;

    protected function setUp()
    {
        $this->registry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');

        $this->securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->transformer = new EventsToUsersTransformer($this->registry, $this->securityFacade);
    }

    public function testTransform()
    {
        $this->assertNull($this->transformer->transform(null));

        $firstUser = new User();
        $firstUser->setUsername('1');
        $secondUser = new User();
        $secondUser->setUsername('2');

        $firstCalendar = new Calendar();
        $firstCalendar->setOwner($firstUser);
        $secondCalendar = new Calendar();
        $secondCalendar->setOwner($secondUser);

        $firstEvent = new CalendarEvent();
        $firstEvent->setCalendar($firstCalendar);
        $secondEvent = new CalendarEvent();
        $secondEvent->setCalendar($secondCalendar);

        $this->assertEquals(
            [$firstUser, $secondUser],
            $this->transformer->transform([$firstEvent, $secondEvent])->toArray()
        );
    }

    public function testReverseTransform()
    {
        $this->assertSame([], $this->transformer->reverseTransform(null));
        $this->assertSame([], $this->transformer->reverseTransform([]));

        $organizationId = 42;

        $firstUser = new User();
        $firstUser->setId(1)
            ->setUsername('1');
        $secondUser = new User();
        $secondUser->setId(2)
            ->setUsername('2');

        $firstCalendar = new Calendar();

        $this->securityFacade->expects($this->any())
            ->method('getOrganizationId')
            ->will($this->returnValue($organizationId));

        $calendarRepository = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Entity\Repository\CalendarRepository')
            ->disableOriginalConstructor()
            ->getMock();
        $calendarRepository->expects($this->once())
            ->method('findDefaultCalendars')
            ->with([1, 2], $organizationId)
            ->will($this->returnValue([$firstCalendar]));

        $this->registry->expects($this->any())
            ->method('getRepository')
            ->with('OroCalendarBundle:Calendar')
            ->will($this->returnValue($calendarRepository));

        $events = $this->transformer->reverseTransform([$firstUser, $secondUser]);
        $this->assertCount(1, $events);

        /** @var CalendarEvent $event */
        $event = $events->first();
        $this->assertInstanceOf('Oro\Bundle\CalendarBundle\Entity\CalendarEvent', $event);
        $this->assertEquals($firstCalendar, $event->getCalendar());
    }

    /**
     * @expectedException \Symfony\Component\Form\Exception\TransformationFailedException
     * @expectedExceptionMessage Can't get current organization
     */
    public function testReverseTransformNoOrganizationId()
    {
        $this->transformer->reverseTransform([new User()]);
    }
}
