<?php

namespace Oro\Bundle\EmailBundle\Tests\Unit\Validator;

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
        $this->context->expects($this->once())
            ->method('addViolation')
            ->with($this->constraint->message);
        $this->getValidator()->validate('test wrong email', $this->constraint);
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
