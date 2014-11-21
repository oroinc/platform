<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\FormEvents;

use Oro\Bundle\CalendarBundle\Form\Type\CalendarEventType;

class CalendarEventTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var CalendarEventType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new CalendarEventType(array());
    }

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
                'textarea',
                array('required' => false, 'label' => 'oro.calendar.calendarevent.description.label')
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'start',
                'oro_datetime',
                array('required' => true, 'label' => 'oro.calendar.calendarevent.start.label')
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'end',
                'oro_datetime',
                array('required' => true, 'label' => 'oro.calendar.calendarevent.end.label')
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
                'reminders',
                'oro_reminder_collection',
                array('required' => false, 'label' => 'oro.reminder.entity_plural_label')
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(6))
            ->method('add')
            ->with(
                'childEvents',
                'oro_calendar_event_invitees',
                array('required' => false, 'label' => 'oro.calendar.calendarevent.invitation.label')
            )
            ->will($this->returnSelf());

        $builder->expects($this->at(7))
            ->method('addEventListener')
            ->with(FormEvents::PRE_SUBMIT, [$this->type, 'onPreSubmit']);

        $childBuilder = $this->getMock('Symfony\Component\Form\FormBuilderInterface');
        $childBuilder->expects($this->once())
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->type, 'onChildPostSubmit']);

        $builder->expects($this->at(8))
            ->method('get')
            ->with('childEvents')
            ->will($this->returnValue($childBuilder));

        $builder->expects($this->at(9))
            ->method('addEventListener')
            ->with(FormEvents::POST_SUBMIT, [$this->type, 'onPostSubmit']);

        $this->type->buildForm($builder, array());
    }

    public function testSetDefaultOptions()
    {
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolverInterface');
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with(
                array(
                    'data_class' => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
                    'intention'  => 'calendar_event',
                )
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_calendar_event', $this->type->getName());
    }
}
