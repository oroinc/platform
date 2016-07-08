<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventType;
use Oro\Bundle\EntityExtendBundle\Tests\Unit\Fixtures\TestEnumValue;

class CalendarEventTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CalendarEventType
     */
    protected $type;

    protected function setUp()
    {
        $repository = $this->getMock('Doctrine\Common\Persistence\ObjectRepository');
        $repository->expects($this->any())
            ->method('find')
            ->will($this->returnCallback(function ($id) {
                return new TestEnumValue($id, $id);
            }));

        $managerRegistry = $this->getMock('Doctrine\Common\Persistence\ManagerRegistry');
        $managerRegistry->expects($this->any())
            ->method('getRepository')
            ->with('Extend\Entity\EV_Ce_Attendee_Status')
            ->will($this->returnValue($repository));

        $securityFacade = $this->getMockBuilder('Oro\Bundle\SecurityBundle\SecurityFacade')
            ->disableOriginalConstructor()
            ->getMock();

        $this->type = new CalendarEventType($managerRegistry, $securityFacade);
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
                ['required' => true, 'label' => 'oro.calendar.calendarevent.title.label']
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'description',
                'textarea',
                ['required' => false, 'label' => 'oro.calendar.calendarevent.description.label']
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
                ['required' => false, 'label' => 'oro.calendar.calendarevent.all_day.label']
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(5))
            ->method('add')
            ->with(
                'backgroundColor',
                'oro_simple_color_picker',
                [
                    'required'           => false,
                    'label'              => 'oro.calendar.calendarevent.background_color.label',
                    'color_schema'       => 'oro_calendar.event_colors',
                    'empty_value'        => 'oro.calendar.calendarevent.no_color',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(6))
            ->method('add')
            ->with(
                'reminders',
                'oro_reminder_collection',
                ['required' => false, 'label' => 'oro.reminder.entity_plural_label']
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(7))
            ->method('add')
            ->with(
                'attendees',
                'oro_calendar_event_attendees_select',
                [
                    'required' => false,
                    'label' => 'oro.calendar.calendarevent.attendees.label',
                    'layout_template' => false,
                ]
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(8))
            ->method('add')
            ->with(
                'notifyInvitedUsers',
                'hidden',
                ['mapped' => false]
            )
            ->will($this->returnSelf());

        $this->type->buildForm($builder, ['layout_template' => false]);
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                [
                    'allow_change_calendar' => false,
                    'layout_template'       => false,
                    'data_class'            => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                    'intention'             => 'calendar_event',
                    'csrf_protection'       => false,
                ]
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_calendar_event', $this->type->getName());
    }
}
