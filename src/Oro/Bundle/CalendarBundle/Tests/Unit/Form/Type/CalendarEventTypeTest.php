<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

use Doctrine\Common\Collections\ArrayCollection;

use Oro\Bundle\CalendarBundle\Entity\Calendar;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent;
use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventType;

class CalendarEventTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CalendarEventType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new CalendarEventType();
    }

    /**
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'title',
                'text',
                array('required' => true, 'label' => 'oro.calendar.calendarevent.title.label')
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'description',
                'oro_resizeable_rich_text',
                array('required' => false, 'label' => 'oro.calendar.calendarevent.description.label')
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'start',
                'oro_datetime',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.start.label',
                    'attr'     => ['class' => 'start'],
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'end',
                'oro_datetime',
                [
                    'required' => true,
                    'label'    => 'oro.calendar.calendarevent.end.label',
                    'attr'     => ['class' => 'end'],
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(4))
            ->method('add')
            ->with(
                'allDay',
                'checkbox',
                array('required' => false, 'label' => 'oro.calendar.calendarevent.all_day.label')
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(5))
            ->method('add')
            ->with(
                'backgroundColor',
                'oro_simple_color_picker',
                array(
                    'required'           => false,
                    'label'              => 'oro.calendar.calendarevent.background_color.label',
                    'color_schema'       => 'oro_calendar.event_colors',
                    'empty_value'        => 'oro.calendar.calendarevent.no_color',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true
                )
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(6))
            ->method('add')
            ->with(
                'reminders',
                'oro_reminder_collection',
                array('required' => false, 'label' => 'oro.reminder.entity_plural_label')
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(7))
            ->method('add')
            ->with(
                'childEvents',
                'oro_calendar_event_invitees',
                array('required' => false, 'label' => 'oro.calendar.calendarevent.invitation.label')
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(8))
            ->method('add')
            ->with(
                'notifyInvitedUsers',
                'hidden',
                array('mapped' => false)
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(9))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SET_DATA, [$this->type, 'preSetData']);

        $builder->expects($this->at(10))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, [$this->type, 'preSubmit']);

        $childBuilder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $childBuilder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->type, 'postSubmitChildEvents']);

        $builder->expects($this->at(11))
            ->method('get')
            ->with('childEvents')
            ->will($this->returnValue($childBuilder));

        $builder->expects($this->at(12))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->type, 'postSubmit']);

        $this->type->buildForm($builder, array());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                array(
                    'allow_change_calendar' => false,
                    'layout_template'       => false,
                    'data_class'            => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                    'intention'             => 'calendar_event',
                )
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_calendar_event', $this->type->getName());
    }

    public function testPreSubmit()
    {
        $calendarEvent = new CalendarEvent();
        $calendarEvent->setTitle('test');

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($calendarEvent));

        $this->assertAttributeEmpty('parentEvent', $this->type);
        $this->type->preSubmit(new FormEvent($form, []));
        $this->assertAttributeEquals($calendarEvent, 'parentEvent', $this->type);
    }

    public function testPostSubmitChildEvents()
    {
        $firstCalendar = new Calendar();
        $firstCalendar->setName('1');
        $secondCalendar = new Calendar();
        $secondCalendar->setName('2');

        $firstEvent = new CalendarEvent();
        $firstEvent->setTitle('1')
            ->setCalendar($firstCalendar);
        $secondEvent = new CalendarEvent();
        $secondEvent->setTitle('2')
            ->setCalendar($secondCalendar);

        $firstExistingEvent = new CalendarEvent();
        $firstExistingEvent->setTitle('1_existing')
            ->setCalendar($firstCalendar);

        $parentEvent = new CalendarEvent();
        $parentEvent->addChildEvent($firstExistingEvent);

        $parentForm = $this->getMock('Symfony\Component\Form\FormInterface');
        $parentForm->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($parentEvent));

        $events = new ArrayCollection([$firstEvent, $secondEvent]);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($events));

        $this->type->preSubmit(new FormEvent($parentForm, []));
        $this->type->postSubmitChildEvents(new FormEvent($form, []));

        $this->assertCount(2, $events);
        $this->assertEquals($firstExistingEvent, $events[0]);
        $this->assertEquals($secondEvent, $events[1]);
    }

    public function testOnSubmit()
    {
        // set default empty data
        $firstEvent = new CalendarEvent();
        $firstEvent->setTitle('1');
        $secondEvent = new CalendarEvent();
        $secondEvent->setTitle('2');

        $parentEvent = new CalendarEvent();
        $parentEvent->setTitle('parent title')
            ->setDescription('parent description')
            ->setStart(new \DateTime('now'))
            ->setEnd(new \DateTime('now'))
            ->setAllDay(true);
        $parentEvent->addChildEvent($firstEvent)
            ->addChildEvent($secondEvent);

        $form = $this->getMock('Symfony\Component\Form\FormInterface');
        $form->expects($this->any())
            ->method('getData')
            ->will($this->returnValue($parentEvent));

        // assert default data with default status
        $this->type->postSubmit(new FormEvent($form, []));

        $this->assertEquals(CalendarEvent::ACCEPTED, $parentEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::NOT_RESPONDED, $firstEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::NOT_RESPONDED, $secondEvent->getInvitationStatus());
        $this->assertEventDataEquals($parentEvent, $firstEvent);
        $this->assertEventDataEquals($parentEvent, $secondEvent);

        // modify data
        $parentEvent->setTitle('modified title')
            ->setDescription('modified description')
            ->setStart(new \DateTime('tomorrow'))
            ->setEnd(new \DateTime('tomorrow'))
            ->setAllDay(false);

        $parentEvent->setInvitationStatus(CalendarEvent::ACCEPTED);
        $firstEvent->setInvitationStatus(CalendarEvent::DECLINED);
        $secondEvent->setInvitationStatus(CalendarEvent::TENTATIVELY_ACCEPTED);

        // assert modified data
        $this->type->postSubmit(new FormEvent($form, []));

        $this->assertEquals(CalendarEvent::ACCEPTED, $parentEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::DECLINED, $firstEvent->getInvitationStatus());
        $this->assertEquals(CalendarEvent::TENTATIVELY_ACCEPTED, $secondEvent->getInvitationStatus());
        $this->assertEventDataEquals($parentEvent, $firstEvent);
        $this->assertEventDataEquals($parentEvent, $secondEvent);
    }

    /**
     * @param CalendarEvent $expected
     * @param CalendarEvent $actual
     */
    protected function assertEventDataEquals(CalendarEvent $expected, CalendarEvent $actual)
    {
        $this->assertEquals($expected->getTitle(), $actual->getTitle());
        $this->assertEquals($expected->getDescription(), $actual->getDescription());
        $this->assertEquals($expected->getStart(), $actual->getStart());
        $this->assertEquals($expected->getEnd(), $actual->getEnd());
        $this->assertEquals($expected->getAllDay(), $actual->getAllDay());
    }
}
