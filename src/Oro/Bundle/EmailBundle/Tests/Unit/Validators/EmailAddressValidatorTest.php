<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

use Symfony\Component\Validator\ConstraintViolationList;

use Oro\Bundle\EmailBundle\Tools\EmailAddressHelper;
use Oro\Bundle\EmailBundle\Validator\Constraints\EmailAddress;
use Oro\Bundle\EmailBundle\Validator\EmailAddressValidator;

class EmailAddressValidatorTest extends \PHPUnit_Framework_TestCase
{
    /** @var EmailAddress */
    protected $constraint;

    /** @var \PHPUnit_Framework_MockObject_MockObject */
    protected $context;

    /** @var EmailAddressHelper */
    protected $emailAddressHelper;

    protected function setUp()
    {
        $this->constraint = new EmailAddress();
        $this->context = $this->getMock('Symfony\Component\Validator\ExecutionContextInterface');
        $this->emailAddressHelper = new EmailAddressHelper();
    }

    public function testValidateNoErrors()
    {
        $violationList = new ConstraintViolationList();
        $this->context->expects($this->once())
            ->method('getViolations')
            ->will($this->returnValue($violationList));
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->getValidator()->validate('testname <test@mail.com>', $this->constraint);
    }

    public function testValidateEmptyValue()
    {
        $this->context->expects($this->never())
            ->method('addViolation');
        $this->getValidator()->validate('', $this->constraint);
    }

    public function testValidateWithErrors()
    {
        $violationList = new ConstraintViolationList();
        $violation1 = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolation')
            ->disableOriginalConstructor()
            ->getMock();
        $violation1->expects($this->any())
            ->method('getPropertyPath')
            ->will($this->returnValue('from'));
        $violationList->add($violation1);
        $violation2 = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolation')
            ->disableOriginalConstructor()
            ->getMock();
        $violation2->expects($this->any())
            ->method('getPropertyPath')
            ->will($this->returnValue('to'));
        $violationList->add($violation2);
        $violation3 = $this->getMockBuilder('Symfony\Component\Validator\ConstraintViolation')
            ->disableOriginalConstructor()
            ->getMock();
        $violation3->expects($this->any())
            ->method('getPropertyPath')
            ->will($this->returnValue('cc'));
        $violationList->add($violation3);

        $this->context->expects($this->any())
            ->method('getViolations')
            ->will($this->returnValue($violationList));
        $this->context->expects($this->any())
            ->method('addViolation')
            ->with($this->constraint->message);
        $this->context->expects($this->any())
            ->method('getPropertyPath')
            ->will($this->returnValue('cc'));
        $this->getValidator()->validate(['test wrong email 1', 'test wrong email 2'], $this->constraint);
    }

    /**
     * @return EmailAddressValidator
     */
    protected function getValidator()
    {
        $validator = new EmailAddressValidator($this->emailAddressHelper);
        $validator->initialize($this->context);

        return $validator;
    }
}
