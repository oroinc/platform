<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType;

use Oro\Bundle\CalendarBundle\Entity\Recurrence;
use Oro\Bundle\CalendarBundle\Form\Type\RecurrenceFormType;

class RecurrenceFormTypeTest extends \PHPUnit_Framework_TestCase
{
    /** @var RecurrenceFormType */
    protected $type;

    protected function setUp()
    {
        $this->type = new RecurrenceFormType();
    }

    public function testBuildForm()
    {
        $builder = $this->getMockBuilder('Symfony\Component\Form\FormBuilder')
            ->disableOriginalConstructor()
            ->getMock();

        $builder->expects($this->at(0))
            ->method('add')
            ->with(
                'recurrenceType',
                'choice',
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.entity_label',
                    'empty_value' => false,
                    'choices' => [
                        Recurrence::TYPE_DAILY => 'oro.calendar.recurrence.types.daily',
                        Recurrence::TYPE_WEEKLY => 'oro.calendar.recurrence.types.weekly',
                        Recurrence::TYPE_MONTHLY => 'oro.calendar.recurrence.types.monthly',
                        Recurrence::TYPE_MONTH_N_TH => 'oro.calendar.recurrence.types.monthnth',
                        Recurrence::TYPE_YEARLY => 'oro.calendar.recurrence.types.yearly',
                        Recurrence::TYPE_YEAR_N_TH => 'oro.calendar.recurrence.types.yearnth',
                    ],
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(1))
            ->method('add')
            ->with(
                'interval',
                'integer',
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.interval.label',
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(2))
            ->method('add')
            ->with(
                'instance',
                'choice',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.instance.label',
                    'empty_value' => false,
                    'choices' => [
                        Recurrence::INSTANCE_FIRST => 'oro.calendar.recurrence.instances.first',
                        Recurrence::INSTANCE_SECOND => 'oro.calendar.recurrence.instances.second',
                        Recurrence::INSTANCE_THIRD => 'oro.calendar.recurrence.instances.third',
                        Recurrence::INSTANCE_FOURTH => 'oro.calendar.recurrence.instances.fourth',
                        Recurrence::INSTANCE_LAST => 'oro.calendar.recurrence.instances.last',
                    ],
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(3))
            ->method('add')
            ->with(
                'dayOfWeek',
                'choice',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.day_of_week.label',
                    'multiple' => true,
                    'choices' => [
                        Recurrence::DAY_SUNDAY => 'oro.calendar.recurrence.days.sunday',
                        Recurrence::DAY_MONDAY => 'oro.calendar.recurrence.days.monday',
                        Recurrence::DAY_TUESDAY => 'oro.calendar.recurrence.days.tuesday',
                        Recurrence::DAY_WEDNESDAY => 'oro.calendar.recurrence.days.wednesday',
                        Recurrence::DAY_THURSDAY => 'oro.calendar.recurrence.days.thursday',
                        Recurrence::DAY_FRIDAY => 'oro.calendar.recurrence.days.friday',
                        Recurrence::DAY_SATURDAY => 'oro.calendar.recurrence.days.saturday',
                    ],
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(4))
            ->method('add')
            ->with(
                'dayOfMonth',
                'integer',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.day_of_month.label',
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(5))
            ->method('add')
            ->with(
                'monthOfYear',
                'integer',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.month_of_year.label',
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(6))
            ->method('add')
            ->with(
                'startTime',
                'datetime',
                [
                    'required' => true,
                    'label' => 'oro.calendar.recurrence.start_time.label',
                    'with_seconds' => true,
                    'model_timezone' => 'UTC',
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(7))
            ->method('add')
            ->with(
                'endTime',
                'datetime',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.end_time.label',
                    'with_seconds' => true,
                    'model_timezone' => 'UTC',
                    'widget' => 'single_text',
                    'format' => DateTimeType::HTML5_FORMAT,
                ]
            )
            ->will($this->returnSelf());
        $builder->expects($this->at(8))
            ->method('add')
            ->with(
                'occurences',
                'integer',
                [
                    'required' => false,
                    'label' => 'oro.calendar.recurrence.occurrences.label',
                    'property_path' => 'occurrences',
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
                'intention' => 'oro_calendar_event_recurrence',
                'data_class' => 'Oro\Bundle\CalendarBundle\Entity\Recurrence',
            ]);

        $this->type->setDefaultOptions($resolver);
    }

    public function testGetName()
    {
        $this->assertEquals('oro_calendar_event_recurrence', $this->type->getName());
    }
}
