<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Oro\Bundle\CalendarBundle\Form\Type\SystemCalendarType;

class SystemCalendarTypeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var SystemCalendarType
     */
    protected $type;

    protected function setUp()
    {
        $this->type = new SystemCalendarType(array());
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'name',
                'text',
                ['required' => true, 'label' => 'oro.calendar.systemcalendar.name.label']
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'backgroundColor',
                'oro_simple_color_picker',
                [
                    'required'           => false,
                    'label'              => 'oro.calendar.systemcalendar.backgroundColor.label',
                    'color_schema'       => 'oro_calendar.calendar_colors',
                    'empty_value'        => 'oro.calendar.systemcalendar.no_color.label',
                    'allow_empty_color'  => true,
                    'allow_custom_color' => true
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'public',
                'choice',
                [
                    'required'      => false,
                    'empty_value'   => false,
                    'choices'       => [
                        true  => 'oro.calendar.systemcalendar.scope.organization',
                        false => 'oro.calendar.systemcalendar.scope.system',
                    ]
                ]
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
                    'data_class' => 'Oro\Bundle\CalendarBundle\Entity\SystemCalendar',
                    'intention'  => 'system_calendar',
                )
            );

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_system_calendar', $this->type->getName());
    }
}
