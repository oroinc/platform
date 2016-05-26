<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator;

use Oro\Bundle\CalendarBundle\Validator\Constraints\Recurrence;
use Oro\Bundle\CalendarBundle\Entity\Recurrence as EntityRecurrence;
use Oro\Bundle\CalendarBundle\Model\Recurrence as ModelRecurrence;
use Oro\Bundle\CalendarBundle\Validator\RecurrenceValidator;

class RecurrenceValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var Recurrence */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $strategy;

    protected function setUp()
    {
        $this->constraint = new Recurrence();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
    }

    public function testValidateNoErrors()
    {
        $this->context->expects($this->never())
            ->method('addViolation');

        $recurrence = new EntityRecurrence();
        $recurrence->setRecurrenceType('daily');

        $this->getValidator()->validate($recurrence, $this->constraint);
    }

    public function testValidateWithErrors()
    {
        $this->context->expects($this->at(0))
            ->method('addViolation')
            ->with($this->equalTo("Parameter 'recurrenceType' must have one of the values: {{ values }}."));
        $this->context->expects($this->at(1))
            ->method('addViolation')
            ->with($this->equalTo("Parameter 'dayOfWeek' can have values from the list: {{ values }}."));
        $this->context->expects($this->at(2))
            ->method('addViolation')
            ->with($this->equalTo("Parameter 'endTime' date can't be earlier than startTime date."));
        $error = 'strategy error message';
        $this->context->expects($this->at(3))
            ->method('addViolation')
            ->with($this->equalTo($error));

        $validator = $this->getValidator();

        $recurrence = new EntityRecurrence();
        $validator->validate($recurrence, $this->constraint);

        $recurrence->setRecurrenceType('daily')
            ->setDayOfWeek(['today']);
        $validator->validate($recurrence, $this->constraint);

        $recurrence->setStartTime(new \DateTime())
            ->setDayOfWeek(null)
            ->setEndTime(new \DateTime('-3 day'));
        $validator->validate($recurrence, $this->constraint);

        $this->strategy->expects($this->once())
            ->method('getValidationErrorMessage')
            ->willReturn($error);
        $recurrence->setEndTime(null)
            ->setDayOfWeek(null);
        $validator->validate($recurrence, $this->constraint);
    }

    /**
     * @return RecurrenceValidator
     */
    protected function getValidator()
    {
        $validator = $this->getMockBuilder('Symfony\Component\Validator\Validator\ValidatorInterface')
            ->getMock();
        $this->strategy = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Model\Recurrence\StrategyInterface')
            ->getMock();

        $recurrenceModel = new ModelRecurrence($validator, $this->strategy);

        $validator = new RecurrenceValidator($recurrenceModel);
        $validator->initialize($this->context);

        return $validator;
    }
}
