<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

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
                'backgroundColor',
                'oro_simple_color_picker',
                array(
                    'required'           => false,
                    'label'              => 'oro.calendar.calendarevent.backgroundColor.label',
                    'color_schema'       => 'oro_calendar.event_colors',
                    'empty_value'        => 'oro.calendar.form.no_color',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true
                )
            )
            ->will($this->returnSelf());

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
}
