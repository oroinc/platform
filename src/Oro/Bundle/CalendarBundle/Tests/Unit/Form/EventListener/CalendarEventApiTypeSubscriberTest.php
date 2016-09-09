<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\EventListener;

use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;

use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Form\EventListener\CalendarEventApiTypeSubscriber;
use Oro\Bundle\CalendarBundle\Manager\CalendarEventManager;

class CalendarEventApiTypeSubscriberTest extends \PHPUnit_Framework_TestCase
{
    /** @var CalendarEventManager|\PHPUnit_Framework_MockObject_MockObject */
    protected $calendarEventManager;

    /** @var RequestStack */
    protected $requestStack;

    /** @var CalendarEventApiTypeSubscriber */
    protected $calendarEventApiTypeSubscriber;

    public function setUp()
    {
        $this->calendarEventManager = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Manager\CalendarEventManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->requestStack = new RequestStack();

        $this->calendarEventApiTypeSubscriber = new CalendarEventApiTypeSubscriber(
            $this->calendarEventManager,
            $this->requestStack
        );
    }

    public function testGetSubscribedEvents()
    {
        $this->assertEquals(
            [
                FormEvents::PRE_SET_DATA => 'preSetData',
                FormEvents::PRE_SUBMIT   => 'preSubmit',
                FormEvents::POST_SUBMIT  => 'postSubmitData',
            ],
            CalendarEventApiTypeSubscriber::getSubscribedEvents()
        );
    }

    public function testPreSetDataShouldRemoveInvitedUsers()
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->requestStack->push(new Request([], ['attendees' => []]));

        $event = new FormEvent($form, new CalendarEvent());
        $form->expects($this->once())
            ->method('remove')
            ->with('invitedUsers');

        $this->calendarEventApiTypeSubscriber->preSetData($event);
    }

    /**
     * @dataProvider testPreSetDataShouldNotRemoveInvitedUsersProvider
     */
    public function testPreSetDataShouldNotRemoveInvitedUsers($data, $request)
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->requestStack->push($request);

        $event = new FormEvent($form, $data);
        $form->expects($this->never())
            ->method('remove');

        $this->calendarEventApiTypeSubscriber->preSetData($event);
    }

    public function testPreSetDataShouldNotRemoveInvitedUsersProvider()
    {
        return [
            [
                null,
                new Request([], ['attendees' => []]),
            ],
            [
                new CalendarEvent(),
                new Request(),
            ],
        ];
    }

    public function testPreSubmitShouldRemoveInvitedUsers()
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->requestStack->push(new Request([], ['attendees' => []]));

        $event = new FormEvent($form, ['recurrence' => ['occurences' => 1]]);
        $form->expects($this->once())
            ->method('remove')
            ->with('invitedUsers');

        $this->calendarEventApiTypeSubscriber->preSubmit($event);
    }

    public function testPreSubmitShouldNotRemoveInvitedUsers()
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');

        $this->requestStack->push(new Request());

        $event = new FormEvent($form, ['recurrence' => ['occurences' => 1]]);
        $form->expects($this->never())
            ->method('remove');

        $this->calendarEventApiTypeSubscriber->preSubmit($event);
    }

    public function testPreSubmitShouldRemoveRecurrence()
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $recurrenceForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $recurrence = new Recurrence();
        $this->requestStack->push(new Request());

        $event = new FormEvent($form, ['id' => 1, 'recurrence' => []]);
        $form->expects($this->any())
            ->method('get')
            ->with('recurrence')
            ->will($this->returnValue($recurrenceForm));
        $recurrenceForm->expects($this->once())
            ->method('getData')
            ->will($this->returnValue($recurrence));
        $recurrenceForm->expects($this->once())
            ->method('setData')
            ->with(null);

        $this->calendarEventManager->expects($this->once())
            ->method('removeRecurrence')
            ->with($recurrence);

        $this->calendarEventApiTypeSubscriber->preSubmit($event);
        $this->assertEquals(['id' => 1], $event->getData());
    }

    /**
     * @dataProvider testPostSubmitDataShouldNotSetCalendarProvider
     */
    public function testPostSubmitDataShouldNotSetCalendar($calendar, CalendarEvent $calendarEvent = null)
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $event = new FormEvent($form, $calendarEvent);

        $calendarForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $calendarForm->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($calendar));

        $calendarAliasForm = $this->getMock('Symfony\Component\Form\FormInterface');

        $form->expects($this->any())
            ->method('get')
            ->with('calendar')
            ->will($this->returnValue($calendarForm));
        $form->expects($this->any())
            ->method('get')
            ->with('calendarAlias')
            ->will($this->returnValue($calendarAliasForm));

        $this->calendarEventManager->expects($this->never())
            ->method('setCalendar');

        $this->calendarEventApiTypeSubscriber->postSubmitData($event);
    }

    public function testPostSubmitDataShouldNotSetCalendarProvider()
    {
        return [
            [
                null,
                null,
            ],
            [
                null,
                new CalendarEvent(),
            ],
        ];
    }

    /**
     * @dataProvider testPostSubmitDataShouldSetCalendarProvider
     */
    public function testPostSubmitDataShouldSetCalendar(CalendarEvent $calendarEvent, $calendar, $alias, $expectedAlias)
    {
        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $event = new FormEvent($form, $calendarEvent);

        $calendarForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $calendarForm->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($calendar));

        $calendarAliasForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $calendarAliasForm->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($alias));

        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($calendarEvent));
        $form->expects($this->any())
            ->method('get')
            ->will($this->returnValueMap([
                ['calendar', $calendarForm],
                ['calendarAlias', $calendarAliasForm],
            ]));

        $this->calendarEventManager->expects($this->once())
            ->method('setCalendar')
            ->with($calendarEvent, $expectedAlias, $calendar);

        $this->calendarEventApiTypeSubscriber->postSubmitData($event);
    }

    public function testPostSubmitDataShouldSetCalendarProvider()
    {
        return [
            [
                new CalendarEvent(),
                1,
                'alias',
                'alias',
            ],
            [
                new CalendarEvent(),
                1,
                null,
                'user',
            ],
        ];
    }
}
