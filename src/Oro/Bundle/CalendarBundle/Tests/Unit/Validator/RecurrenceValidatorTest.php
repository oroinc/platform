<?php

namespace Oro\Bundle\CalendarBundle\Tests\Unit\Validator;

use Oro\Bundle\CalendarBundle\Validator\Constraints\Recurrence;
use Oro\Bundle\CalendarBundle\Entity\Recurrence as EntityRecurrence;
use Oro\Bundle\CalendarBundle\Validator\RecurrenceValidator;

class EmailRecipientsValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var Recurrence */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

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

        $this->getValidator()->validate($recurrence, $this->constraint);
    }

    public function testValidateWithErrors()
    {
        $this->context->expects($this->once())
            ->method('addViolation');

        $recurrence = new EntityRecurrence();
        $recurrence->setStartTime(new \DateTime());
        $recurrence->setEndTime(new \DateTime('-3 day'));

        $this->getValidator()->validate($recurrence, $this->constraint);
    }

    /**
     * @return RecurrenceValidator
     */
    protected function getValidator()
    {
        $recurrenceStrategy = $this->getMockBuilder('Oro\Bundle\CalendarBundle\Strategy\Recurrence\StrategyInterface')
            ->getMock();
        $recurrenceStrategy->expects($this->once())
            ->method('getValidationErrorMessage');

        $validator = new RecurrenceValidator($recurrenceStrategy);
        $validator->initialize($this->context);

        return $validator;
    }
}
