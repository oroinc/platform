<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator;

use Oro\Bundle\CalendarBundle\Validator\CalendarEventValidator;
use Oro\Bundle\CalendarBundle\Validator\Constraints\CalendarEvent;
use Oro\Bundle\CalendarBundle\Entity\CalendarEvent as CalendarEventEntity;

class CalendarEventValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var CalendarEvent */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    protected function setUp()
    {
        $this->constraint = new CalendarEvent();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
    }

    public function testValidateNoErrors()
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $calendarEvent = new CalendarEventEntity();

        $this->getValidator()->validate($calendarEvent, $this->constraint);
    }

    public function testValidateWithErrors()
    {
        $this->context->expects($this->at(0))
            ->method('addViolation')
            ->with($this->equalTo("Parameter 'recurringEventId' can't have the same value as calendar event ID."));
        $this->context->expects($this->at(1))
            ->method('addViolation')
            ->with($this->equalTo("Parameter 'recurringEventId' can be set only for recurring calendar events."));

        $calendarEvent = new CalendarEventEntity();
        $recurringEvent = new CalendarEventEntity();
        $this->setId($recurringEvent, 666);
        $this->setId($calendarEvent, 666);
        $calendarEvent->setRecurringEvent($recurringEvent);

        $this->getValidator()->validate($calendarEvent, $this->constraint);
    }

    /**
     * @return CalendarEventValidator
     */
    protected function getValidator()
    {
        $validator = new CalendarEventValidator();
        $validator->initialize($this->context);

        return $validator;
    }

    /**
     * @param $object
     * @param $value
     */
    protected function setId($object, $value)
    {
        $class = new \ReflectionClass($object);
        $prop  = $class->getProperty('id');
        $prop->setAccessible(true);

        $prop->setValue($object, $value);
    }
}
