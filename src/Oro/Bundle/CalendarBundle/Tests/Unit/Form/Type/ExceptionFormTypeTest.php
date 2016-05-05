<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

use Oro\Bundle\CalendarBundle\Form\Type\ExceptionFormType;

class ExceptionFormTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var ExceptionFormType */
    protected $type;

    protected function setUp()
    {
        $this->type = new ExceptionFormType();
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
                [
                    'required' => true,
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'description',
                'text',
                [
                    'required' => false,
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'start',
                'datetime',
                [
                    'required' => true,
                    'with_seconds' => true,
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'end',
                'datetime',
                [
                    'required' => true,
                    'with_seconds' => true,
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(4))
            ->method('add')
            ->with(
                'originalStart',
                'datetime',
                [
                    'required' => true,
                    'with_seconds' => true,
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                    'model_timezone' => 'UTC',
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(5))
            ->method('add')
            ->with(
                'allDay',
                'checkbox',
                [
                    'required' => false,
                ]
            )
            ->will($this->returnSelf());

        $this->type->buildForm($builder, []);
    }

    public function testConfigureOptions()
    {
        $resolver = $this->getMockBuilder('Symfony\Component\OptionsResolver\OptionsResolver')
            ->disableOriginalConstructor()
            ->getMock();
        $resolver->expects($this->once())
            ->method('setDefaults')
            ->with([
                'csrf_protection' => false,
                'data_class' => 'Oro\Bundle\CalendarBundle\Entity\CalendarEvent',
            ]);

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_calendar_event_exception', $this->type->getName());
    }
}
