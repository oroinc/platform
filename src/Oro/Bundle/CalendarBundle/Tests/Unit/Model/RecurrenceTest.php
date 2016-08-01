<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Model;

use Oro\Bundle\CalendarBundle\Entity;
use Oro\Bundle\CalendarBundle\Model\Recurrence;

class RecurrenceTest extends \PHPUnit_Framework_TestCase
{
    /** @var Recurrence */
    protected $model;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $validator;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $strategy;

    protected function setUp()
    {
        $this->validator = $this->getMockBuilder('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->getMock();

        $this->strategy = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface')
            ->getMock();

        $this->model = new Recurrence($this->validator, $this->strategy);
    }

    public function testGetOccurrences()
    {
        $this->strategy->expects($this->once())
            ->method('getOccurrences');

        $this->model->getOccurrences(new Entity\Recurrence(), new \DateTime(), new \DateTime());
    }

    public function testGetTextValue()
    {
        $this->strategy->expects($this->once())
            ->method('getTextValue');

        $this->model->getTextValue(new Entity\Recurrence());
    }

    public function testGetCalculatedEndTime()
    {
        $this->strategy->expects($this->once())
            ->method('getCalculatedEndTime');

        $this->model->getCalculatedEndTime(new Entity\Recurrence());
    }

    public function testGetValidationErrorMessage()
    {
        $this->strategy->expects($this->once())
            ->method('getValidationErrorMessage');

        $this->model->getValidationErrorMessage(new Entity\Recurrence());
    }

    public function testGetRecurrenceTypesValues()
    {
        $this->assertEquals(
            [
                Recurrence::TYPE_DAILY,
                Recurrence::TYPE_WEEKLY,
                Recurrence::TYPE_MONTHLY,
                Recurrence::TYPE_MONTH_N_TH,
                Recurrence::TYPE_YEARLY,
                Recurrence::TYPE_YEAR_N_TH
            ],
            $this->model->getRecurrenceTypesValues()
        );
    }

    public function testGetDaysOfWeekValues()
    {
        $this->assertEquals(
            [
                Recurrence::DAY_SUNDAY,
                Recurrence::DAY_MONDAY,
                Recurrence::DAY_TUESDAY,
                Recurrence::DAY_WEDNESDAY,
                Recurrence::DAY_THURSDAY,
                Recurrence::DAY_FRIDAY,
                Recurrence::DAY_SATURDAY,
            ],
            $this->model->getDaysOfWeekValues()
        );
    }

    public function testGetRecurrenceTypes()
    {
        $this->assertEquals(
            [
                Recurrence::TYPE_DAILY => 'oro.calendar.recurrence.types.daily',
                Recurrence::TYPE_WEEKLY => 'oro.calendar.recurrence.types.weekly',
                Recurrence::TYPE_MONTHLY => 'oro.calendar.recurrence.types.monthly',
                Recurrence::TYPE_MONTH_N_TH => 'oro.calendar.recurrence.types.monthnth',
                Recurrence::TYPE_YEARLY => 'oro.calendar.recurrence.types.yearly',
                Recurrence::TYPE_YEAR_N_TH => 'oro.calendar.recurrence.types.yearnth',
            ],
            $this->model->getRecurrenceTypes()
        );
    }

    public function testGetInstances()
    {
        $this->assertEquals(
            [
                Recurrence::INSTANCE_FIRST => 'oro.calendar.recurrence.instances.first',
                Recurrence::INSTANCE_SECOND => 'oro.calendar.recurrence.instances.second',
                Recurrence::INSTANCE_THIRD => 'oro.calendar.recurrence.instances.third',
                Recurrence::INSTANCE_FOURTH => 'oro.calendar.recurrence.instances.fourth',
                Recurrence::INSTANCE_LAST => 'oro.calendar.recurrence.instances.last',
            ],
            $this->model->getInstances()
        );
    }

    public function testGetDaysOfWeek()
    {
        $this->assertEquals(
            [
                Recurrence::DAY_SUNDAY => 'oro.calendar.recurrence.days.sunday',
                Recurrence::DAY_MONDAY => 'oro.calendar.recurrence.days.monday',
                Recurrence::DAY_TUESDAY => 'oro.calendar.recurrence.days.tuesday',
                Recurrence::DAY_WEDNESDAY => 'oro.calendar.recurrence.days.wednesday',
                Recurrence::DAY_THURSDAY => 'oro.calendar.recurrence.days.thursday',
                Recurrence::DAY_FRIDAY => 'oro.calendar.recurrence.days.friday',
                Recurrence::DAY_SATURDAY => 'oro.calendar.recurrence.days.saturday',
            ],
            $this->model->getDaysOfWeek()
        );
    }
}
